"use strict";

(function () {
    var politicasActuales = [];

    function request(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    function postRequest(url, data) {
        var headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"};
        if (window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: "POST",
            headers: headers,
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    function destinoSeleccionado() {
        var select = document.getElementById("migbd_destino");
        return select ? select.value : "";
    }

    function activarTab(selector) {
        var trigger = document.querySelector('a[href="' + selector + '"]');
        if (trigger && window.bootstrap) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }

    function timestampArchivo() {
        var fecha = new Date();
        var pad = function (valor) {
            return String(valor).padStart(2, "0");
        };
        return fecha.getFullYear() +
            pad(fecha.getMonth() + 1) +
            pad(fecha.getDate()) + "_" +
            pad(fecha.getHours()) +
            pad(fecha.getMinutes()) +
            pad(fecha.getSeconds());
    }

    function descargarTexto(nombre, contenido, tipoMime) {
        var blob = new Blob([contenido], {type: tipoMime + ";charset=utf-8"});
        var url = URL.createObjectURL(blob);
        var enlace = document.createElement("a");
        enlace.href = url;
        enlace.download = nombre;
        enlace.style.display = "none";
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        URL.revokeObjectURL(url);
    }

    function badgePolitica(politica) {
        var clase = "badge-light-secondary";
        if (politica === "data_merge") {
            clase = "badge-light-primary";
        } else if (politica === "data_seed") {
            clase = "badge-light-success";
        } else if (politica === "schema_only") {
            clase = "badge-light-info";
        } else if (politica === "production_owned") {
            clase = "badge-light-warning";
        } else if (politica === "blocked") {
            clase = "badge-light-danger";
        }
        return '<span class="badge ' + clase + '">' + escapeHtml(politica) + "</span>";
    }

    function selectPolitica(politica, tabla) {
        var opciones = ["schema_only", "data_seed", "data_merge", "data_snapshot", "local_only", "production_owned", "blocked"];
        return '<select class="form-select form-select-sm form-select-solid migbd-politica" data-tabla="' + escapeHtml(tabla) + '">' +
            opciones.map(function (opcion) {
                return '<option value="' + opcion + '"' + (opcion === politica ? " selected" : "") + ">" + opcion + "</option>";
            }).join("") +
            "</select>";
    }

    function inputDecision(clase, tabla, valor, placeholder) {
        return '<input type="text" class="form-control form-control-sm form-control-solid ' + clase + '" data-tabla="' + escapeHtml(tabla) + '" value="' + escapeHtml(valor || "") + '" placeholder="' + escapeHtml(placeholder || "") + '">';
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: renderizar politicas sugeridas sin persistir decisiones.
     * Impacto: UI Migraciones BD; ayuda a revisar que tablas migran como esquema, semilla o merge.
     */
    function cargarPoliticas() {
        var contenedor = document.getElementById("migbd_politicas_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Analizando tablas...</div>';
        activarTab("#migbd_tab_politicas");
        request("/migracionBd/tablas_clasificar").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible clasificar");
            }
            politicasActuales = response.depurar || [];
            var resumen = resumenPoliticas(politicasActuales);
            var filas = politicasActuales.map(function (item) {
                return "<tr>" +
                    '<td><div class="form-check form-check-sm form-check-custom form-check-solid">' +
                    '<input class="form-check-input migbd-tabla-check" type="checkbox" value="' + escapeHtml(item.tabla) + '">' +
                    "</div></td>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.tabla) + "</td>" +
                    '<td>' + (item.persistida ? '<span class="badge badge-light-success">guardada</span>' : '<span class="badge badge-light-info">sugerida</span>') + "</td>" +
                    "<td>" + selectPolitica(item.politica, item.tabla) + "</td>" +
                    '<td><div class="form-check form-switch form-check-custom form-check-solid">' +
                    '<input class="form-check-input migbd-incluye-datos" type="checkbox" data-tabla="' + escapeHtml(item.tabla) + '"' + (item.incluye_datos ? " checked" : "") + ">" +
                    "</div></td>" +
                    "<td>" + inputDecision("migbd-llave-natural", item.tabla, item.llave_natural || "", "sku,codigo,uuid") + "</td>" +
                    "<td>" + inputDecision("migbd-descripcion-politica", item.tabla, item.descripcion || item.motivo || "", "Decision o motivo") + "</td>" +
                    "<td class=\"text-gray-700\">" + escapeHtml(item.motivo) + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="row g-4 mb-5">' +
                resumenBox("Schema only", resumen.schema_only || 0, "info") +
                resumenBox("Data seed", resumen.data_seed || 0, "success") +
                resumenBox("Data merge", resumen.data_merge || 0, "primary") +
                resumenBox("Prod owned", resumen.production_owned || 0, "warning") +
                resumenBox("Blocked", resumen.blocked || 0, "danger") +
                "</div>" +
                '<div class="d-flex flex-wrap gap-3 mb-5">' +
                '<button type="button" class="btn btn-sm btn-light-primary" id="migbd_btn_seleccionar_datos"><i class="bi bi-check2-square"></i> Seleccionar con datos</button>' +
                '<button type="button" class="btn btn-sm btn-light" id="migbd_btn_limpiar_seleccion"><i class="bi bi-square"></i> Limpiar seleccion</button>' +
                '<select class="form-select form-select-sm form-select-solid w-200px" id="migbd_bulk_politica">' +
                '<option value="">Politica masiva</option>' +
                '<option value="schema_only">schema_only</option>' +
                '<option value="data_seed">data_seed</option>' +
                '<option value="data_merge">data_merge</option>' +
                '<option value="production_owned">production_owned</option>' +
                '<option value="blocked">blocked</option>' +
                "</select>" +
                '<button type="button" class="btn btn-sm btn-light-warning" id="migbd_btn_aplicar_bulk"><i class="bi bi-layers"></i> Aplicar a seleccion</button>' +
                "</div>" +
                '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th></th><th>Tabla</th><th>Origen</th><th>Politica</th><th>Datos</th><th>Llave natural</th><th>Descripcion</th><th>Motivo sugerido</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function resumenPoliticas(politicas) {
        return (politicas || []).reduce(function (mapa, item) {
            var politica = item.politica || "blocked";
            mapa[politica] = (mapa[politica] || 0) + 1;
            return mapa;
        }, {});
    }

    function leerPoliticasEditadas() {
        var mapa = {};
        politicasActuales.forEach(function (item) {
            mapa[item.tabla] = {
                tabla: item.tabla,
                politica: item.politica,
                incluye_datos: !!item.incluye_datos,
                llave_natural: item.llave_natural || "",
                descripcion: item.motivo || item.descripcion || ""
            };
        });
        document.querySelectorAll(".migbd-politica").forEach(function (select) {
            var tabla = select.getAttribute("data-tabla");
            if (mapa[tabla]) {
                mapa[tabla].politica = select.value;
            }
        });
        document.querySelectorAll(".migbd-incluye-datos").forEach(function (input) {
            var tabla = input.getAttribute("data-tabla");
            if (mapa[tabla]) {
                mapa[tabla].incluye_datos = input.checked;
            }
        });
        document.querySelectorAll(".migbd-llave-natural").forEach(function (input) {
            var tabla = input.getAttribute("data-tabla");
            if (mapa[tabla]) {
                mapa[tabla].llave_natural = input.value.trim();
            }
        });
        document.querySelectorAll(".migbd-descripcion-politica").forEach(function (input) {
            var tabla = input.getAttribute("data-tabla");
            if (mapa[tabla]) {
                mapa[tabla].descripcion = input.value.trim();
            }
        });
        return Object.keys(mapa).map(function (tabla) {
            return mapa[tabla];
        });
    }

    function seleccionarPoliticasConDatos() {
        document.querySelectorAll(".migbd-tabla-check").forEach(function (check) {
            var tabla = check.value;
            var datos = document.querySelector('.migbd-incluye-datos[data-tabla="' + cssEscape(tabla) + '"]');
            check.checked = !!(datos && datos.checked);
        });
    }

    function limpiarSeleccionPoliticas() {
        document.querySelectorAll(".migbd-tabla-check").forEach(function (check) {
            check.checked = false;
        });
    }

    function aplicarPoliticaMasiva() {
        var select = document.getElementById("migbd_bulk_politica");
        var politica = select ? select.value : "";
        if (!politica) {
            return;
        }
        document.querySelectorAll(".migbd-tabla-check:checked").forEach(function (check) {
            var tabla = check.value;
            var destino = document.querySelector('.migbd-politica[data-tabla="' + cssEscape(tabla) + '"]');
            if (destino) {
                destino.value = politica;
            }
        });
    }

    function cssEscape(value) {
        if (window.CSS && window.CSS.escape) {
            return CSS.escape(value);
        }
        return String(value).replace(/"/g, '\\"');
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: mostrar selfcheck operativo del modulo sin ejecutar cambios.
     * Impacto: UI Migraciones BD; ayuda a preparar respaldo y activacion segura.
     */
    function cargarSelfcheck() {
        var contenedor = document.getElementById("migbd_selfcheck_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Ejecutando selfcheck...</div>';
        activarTab("#migbd_tab_selfcheck");
        request("/migracionBd/selfcheck").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible ejecutar selfcheck");
            }
            var d = response.depurar || {};
            var checks = d.checks || [];
            var cards = checks.map(function (item) {
                var icono = item.ok ? "bi-check-circle" : (item.nivel === "danger" ? "bi-x-circle" : "bi-exclamation-triangle");
                return '<div class="col-xl-3 col-md-6"><div class="border rounded p-4 h-100">' +
                    '<div class="d-flex align-items-center gap-2 mb-2 text-' + colorNivel(item.nivel) + '">' +
                    '<i class="bi ' + icono + '"></i><span class="fw-bold">' + escapeHtml(item.codigo) + "</span></div>" +
                    '<div class="text-gray-700">' + escapeHtml(item.mensaje) + "</div>" +
                    renderDepurarCorto(item.depurar || {}) +
                    "</div></div>";
            }).join("");
            contenedor.innerHTML = '<div class="row g-4 mb-5">' + cards + "</div>" +
                '<div class="row g-4">' +
                resumenBox("Bloqueantes", (d.bloqueantes || []).length, (d.bloqueantes || []).length ? "danger" : "success") +
                resumenBox("Advertencias", (d.advertencias || []).length, (d.advertencias || []).length ? "warning" : "success") +
                resumenBox("Ambientes", (d.ambientes || []).length, "info") +
                resumenBox("Estado", (d.bloqueantes || []).length ? "Revisar" : "Listo base", (d.bloqueantes || []).length ? "warning" : "success") +
                "</div>" +
                '<div class="alert alert-info mt-5 mb-0">' + escapeHtml(d.siguiente_paso || "") + "</div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function colorNivel(nivel) {
        if (nivel === "danger") {
            return "danger";
        }
        if (nivel === "warning") {
            return "warning";
        }
        if (nivel === "info") {
            return "info";
        }
        return "success";
    }

    function renderDepurarCorto(depurar) {
        var keys = Object.keys(depurar || {});
        if (!keys.length) {
            return "";
        }
        return '<pre class="bg-light rounded p-2 mt-3 fs-8 mb-0"><code>' + escapeHtml(JSON.stringify(depurar, null, 2)) + "</code></pre>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-02
     * Proposito: mostrar checklist operativo consolidado.
     * Impacto: UI Migraciones BD; no ejecuta cambios.
     */
    function cargarChecklistOperativo() {
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var paquete = (document.getElementById("migbd_paquete_codigo") || {}).value || "";
        var contenedor = document.getElementById("migbd_checklist_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Generando checklist...</div>';
        activarTab("#migbd_tab_checklist");
        request("/migracionBd/checklist_operativo?respaldo=" + encodeURIComponent(respaldo) + "&paquete=" + encodeURIComponent(paquete)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar checklist");
            }
            var d = response.depurar || {};
            var pasos = d.pasos || [];
            var htmlPasos = pasos.map(function (paso, index) {
                var ok = !!paso.ok;
                return '<div class="d-flex align-items-start gap-4 border rounded p-4 mb-3">' +
                    '<div class="badge ' + (ok ? "badge-light-success" : "badge-light-warning") + ' fs-7">' + (index + 1) + "</div>" +
                    '<div class="flex-grow-1">' +
                    '<div class="fw-bold">' + escapeHtml(paso.titulo) + "</div>" +
                    '<div class="' + (ok ? "text-success" : "text-warning") + '">' + escapeHtml(ok ? "Completo" : paso.accion_pendiente) + "</div>" +
                    "</div>" +
                    '<div>' + (ok ? '<span class="badge badge-light-success">ok</span>' : '<span class="badge badge-light-warning">pendiente</span>') + "</div>" +
                    "</div>";
            }).join("");
            contenedor.innerHTML = '<div class="row g-4 mb-5">' +
                resumenBox("Pasos", pasos.length, "info") +
                resumenBox("Pendientes", (d.pendientes || []).length, (d.pendientes || []).length ? "warning" : "success") +
                resumenBox("Listo", d.listo ? "si" : "no", d.listo ? "success" : "warning") +
                resumenBox("Paquete", paquete || "sin paquete", "dark") +
                "</div>" +
                htmlPasos +
                '<div class="alert alert-info mt-5 mb-0">' + escapeHtml(d.siguiente_paso || "") + "</div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: renderizar perfil read-only de tablas para decidir migracion de datos.
     * Impacto: UI Migraciones BD; no consulta filas reales de negocio.
     */
    function cargarPerfilDatos() {
        var contenedor = document.getElementById("migbd_perfil_datos_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Perfilando tablas...</div>';
        activarTab("#migbd_tab_perfil_datos");
        request("/migracionBd/tablas_perfil_datos").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible perfilar tablas");
            }
            var perfiles = (response.depurar && response.depurar.perfiles) ? response.depurar.perfiles : [];
            var filas = perfiles.map(function (item) {
                return "<tr>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.tabla) + "</td>" +
                    "<td>" + badgePolitica(item.politica_sugerida) + "</td>" +
                    "<td>" + escapeHtml(item.filas_estimadas) + "</td>" +
                    "<td>" + escapeHtml((item.pk || []).join(", ")) + "</td>" +
                    "<td>" + escapeHtml((item.candidatos_llave_natural || []).slice(0, 4).join(", ")) + "</td>" +
                    "<td>" + renderRiesgos(item.riesgos || []) + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th>Tabla</th><th>Politica</th><th>Filas</th><th>PK</th><th>Llave candidata</th><th>Riesgos</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderRiesgos(riesgos) {
        if (!riesgos.length) {
            return '<span class="badge badge-light-success">sin alertas</span>';
        }
        return riesgos.map(function (riesgo) {
            var clase = riesgo === "columnas_sensibles" || riesgo === "propiedad_productivo" ? "badge-light-danger" : "badge-light-warning";
            return '<span class="badge ' + clase + ' me-1 mb-1">' + escapeHtml(riesgo) + "</span>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: renderizar orden sugerido de migracion por dependencias FK.
     * Impacto: UI Migraciones BD; ayuda a planear carga de datos sin ejecutar cambios.
     */
    function cargarOrdenMigracion() {
        var contenedor = document.getElementById("migbd_orden_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Calculando dependencias...</div>';
        activarTab("#migbd_tab_orden");
        request("/migracionBd/tablas_orden_migracion").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular orden");
            }
            var d = response.depurar || {};
            var orden = d.orden || [];
            var ciclos = d.ciclos_o_dependencias_pendientes || [];
            var resumen = '<div class="row g-4 mb-5">' +
                resumenBox("Tablas", d.total_tablas || 0, "primary") +
                resumenBox("Ordenadas", d.ordenadas || 0, "success") +
                resumenBox("Pendientes", d.pendientes || 0, ciclos.length ? "danger" : "info") +
                "</div>";
            var filas = orden.map(function (item) {
                return "<tr>" +
                    "<td>" + escapeHtml(item.orden) + "</td>" +
                    "<td>" + escapeHtml(item.nivel) + "</td>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.tabla) + "</td>" +
                    "<td>" + badgePolitica(item.politica ? item.politica.politica : "blocked") + "</td>" +
                    "<td>" + escapeHtml((item.depende_de || []).join(", ")) + "</td>" +
                    "<td>" + escapeHtml((item.dependientes || []).slice(0, 6).join(", ")) + "</td>" +
                    "</tr>";
            }).join("");
            var html = resumen + '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th>Orden</th><th>Nivel</th><th>Tabla</th><th>Politica</th><th>Depende de</th><th>Dependientes</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
            if (ciclos.length) {
                html += '<div class="alert alert-warning mt-5"><div class="fw-bold mb-2">Dependencias pendientes</div>' +
                    escapeHtml(ciclos.map(function (item) {
                        return item.tabla + " -> " + (item.dependencias_pendientes || []).join(",");
                    }).join(" | ")) + "</div>";
            }
            contenedor.innerHTML = html;
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: renderizar resumen ejecutivo de decision de migracion.
     * Impacto: UI Migraciones BD; muestra agregados de metadatos sin leer datos reales.
     */
    function cargarResumenDecision() {
        var contenedor = document.getElementById("migbd_resumen_decision_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Generando resumen...</div>';
        activarTab("#migbd_tab_resumen_decision");
        request("/migracionBd/resumen_decision").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar resumen");
            }
            var d = response.depurar || {};
            var html = '<div class="row g-4 mb-5">' +
                resumenBox("Tablas", d.total_tablas || 0, "primary") +
                resumenBox("Candidatas datos", (d.candidatas_datos || []).length, "success") +
                resumenBox("Bloqueadas/prod", (d.bloqueadas_o_productivo || []).length, "warning") +
                resumenBox("Sensibles", (d.sensibles || []).length, "danger") +
                "</div>";
            html += '<div class="row g-5 mb-6"><div class="col-xl-6">' + renderMapaResumen("Politicas", d.politicas || {}) + '</div><div class="col-xl-6">' + renderMapaResumen("Riesgos", d.riesgos || {}) + "</div></div>";
            html += '<div class="alert alert-primary">' + escapeHtml(d.recomendacion || "") + "</div>";
            html += renderTablaResumenDecision("Candidatas para datos", d.candidatas_datos || []);
            html += renderTablaResumenDecision("Sin llave clara", d.sin_llave_clara || []);
            html += renderTablaResumenDecision("Columnas sensibles", d.sensibles || []);
            contenedor.innerHTML = html;
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderMapaResumen(titulo, mapa) {
        var filas = Object.keys(mapa).map(function (clave) {
            return '<div class="d-flex justify-content-between border-bottom py-2"><span>' + escapeHtml(clave) + '</span><span class="fw-bold">' + escapeHtml(mapa[clave]) + "</span></div>";
        }).join("");
        return '<div class="border rounded p-5 h-100"><div class="fw-bold mb-3">' + escapeHtml(titulo) + "</div>" + (filas || '<div class="text-muted">Sin datos.</div>') + "</div>";
    }

    function renderTablaResumenDecision(titulo, items) {
        if (!items.length) {
            return '<div class="mb-5"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div><div class="text-muted">Sin tablas.</div></div>';
        }
        var filas = items.slice(0, 60).map(function (item) {
            return "<tr>" +
                "<td class=\"fw-semibold\">" + escapeHtml(item.tabla) + "</td>" +
                "<td>" + badgePolitica(item.politica) + "</td>" +
                "<td>" + escapeHtml(item.filas_estimadas) + "</td>" +
                "<td>" + escapeHtml((item.pk || []).join(", ")) + "</td>" +
                "<td>" + escapeHtml((item.candidatos_llave_natural || []).slice(0, 4).join(", ")) + "</td>" +
                "<td>" + renderRiesgos(item.riesgos || []) + "</td>" +
                "</tr>";
        }).join("");
        var nota = items.length > 60 ? '<div class="text-muted fs-8 mt-2">Mostrando 60 de ' + items.length + " tablas.</div>" : "";
        return '<div class="mb-6"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div><div class="table-responsive">' +
            '<table class="table table-sm table-row-dashed align-middle"><thead><tr class="text-muted text-uppercase fs-8"><th>Tabla</th><th>Politica</th><th>Filas</th><th>PK</th><th>Llave candidata</th><th>Riesgos</th></tr></thead><tbody>' +
            filas + "</tbody></table></div>" + nota + "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: generar manifiesto JSON portable de preparacion.
     * Impacto: UI Migraciones BD; evidencia tecnica sin escritura.
     */
    function generarManifiesto() {
        var destino = destinoSeleccionado();
        var salida = document.getElementById("migbd_manifiesto_resultado");
        salida.textContent = "Generando manifiesto...";
        activarTab("#migbd_tab_manifiesto");
        request("/migracionBd/manifiesto_preparacion?destino=" + encodeURIComponent(destino)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar manifiesto");
            }
            salida.textContent = response.depurar && response.depurar.json ? response.depurar.json : JSON.stringify(response.depurar || {}, null, 2);
        }).catch(function (error) {
            salida.textContent = "-- " + (error.message || String(error));
        });
    }

    function copiarManifiesto() {
        var salida = document.getElementById("migbd_manifiesto_resultado");
        if (!salida || !navigator.clipboard) {
            return;
        }
        navigator.clipboard.writeText(salida.textContent || "").then(function () {
            if (window.Swal) {
                Swal.fire({text: "Manifiesto copiado", icon: "success", confirmButtonText: "Aceptar"});
            }
        });
    }

    function descargarManifiesto() {
        var salida = document.getElementById("migbd_manifiesto_resultado");
        var contenido = salida ? (salida.textContent || "").trim() : "";
        if (!contenido || contenido === "{}" || contenido === "Sin manifiesto generado.") {
            return;
        }
        descargarTexto("migraciones_bd_manifest_" + timestampArchivo() + ".json", contenido, "application/json");
    }

    function tablasSeleccionadas() {
        return Array.prototype.slice.call(document.querySelectorAll(".migbd-tabla-check:checked")).map(function (input) {
            return input.value;
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: guardar preparacion de politicas si el esquema tecnico ya existe.
     * Impacto: UI Migraciones BD; no aplica migraciones.
     */
    function guardarPoliticas() {
        var politicas = leerPoliticasEditadas();
        if (!politicas.length) {
            cargarPoliticas();
            return;
        }
        postRequest("/migracionBd/politicas_guardar", {
            politicas: JSON.stringify(politicas)
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar politicas");
            }
            if (window.Swal) {
                Swal.fire({text: response.mensaje, icon: response.tipo === "info" ? "info" : "success", confirmButtonText: "Aceptar"});
            }
        }).catch(function (error) {
            if (window.Swal) {
                Swal.fire({text: error.message || String(error), icon: "warning", confirmButtonText: "Aceptar"});
            }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: consultar diferencias de esquema local contra destino configurado.
     * Impacto: UI Migraciones BD; no ejecuta cambios.
     */
    function comparar() {
        var destino = destinoSeleccionado();
        var contenedor = document.getElementById("migbd_comparacion_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Comparando ambientes...</div>';
        activarTab("#migbd_tab_comparacion");
        request("/migracionBd/comparar_ambientes?destino=" + encodeURIComponent(destino)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible comparar");
            }
            var comp = response.depurar.comparacion;
            var resumen = comp.resumen || {};
            var riesgos = comp.riesgos || resumen.riesgos || {};
            var html = '<div class="row g-4 mb-5">' +
                resumenBox("Tablas solo local", resumen.tablas_solo_origen || 0, "primary") +
                resumenBox("Tablas solo destino", resumen.tablas_solo_destino || 0, "warning") +
                resumenBox("Columnas faltantes", resumen.columnas_faltantes_destino || 0, "info") +
                resumenBox("Indices faltantes", resumen.indices_faltantes_destino || 0, "success") +
                resumenBox("FKs faltantes", resumen.foraneas_faltantes_destino || 0, "danger") +
                "</div>";
            html += '<div class="row g-4 mb-6">' +
                resumenBox("Riesgo bajo", riesgos.bajo || 0, "success") +
                resumenBox("Riesgo medio", riesgos.medio || 0, "warning") +
                resumenBox("Riesgo alto", riesgos.alto || 0, "danger") +
                resumenBox("Bloqueantes", riesgos.bloqueante || 0, "danger") +
                resumenBox("Revision", riesgos.revision || 0, "dark") +
                "</div>";
            html += renderLista("Tablas solo en local", comp.tablas_solo_origen, ["tabla", "filas_estimadas", "riesgo", "recomendacion"]);
            html += renderLista("Columnas faltantes en destino", comp.columnas_faltantes_destino, ["tabla", "columna", "riesgo", "recomendacion", "definicion"]);
            html += renderLista("Indices faltantes en destino", comp.indices_faltantes_destino, ["tabla", "indice", "riesgo", "recomendacion", "definicion"]);
            html += renderLista("Llaves foraneas faltantes en destino", comp.foraneas_faltantes_destino, ["tabla", "restriccion", "tabla_referencia", "riesgo", "recomendacion", "definicion"]);
            html += renderLista("Columnas diferentes", comp.columnas_diferentes, ["tabla", "columna", "riesgo", "recomendacion"]);
            html += renderLista("Tablas solo en destino", comp.tablas_solo_destino, ["tabla", "filas_estimadas", "riesgo", "recomendacion"]);
            contenedor.innerHTML = html;
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function resumenBox(titulo, valor, color) {
        return '<div class="col-md-3"><div class="border rounded p-4 h-100">' +
            '<div class="text-muted fs-8 text-uppercase">' + escapeHtml(titulo) + "</div>" +
            '<div class="fw-bold fs-2 text-' + color + '">' + escapeHtml(valor) + "</div>" +
            "</div></div>";
    }

    function renderLista(titulo, items, campos) {
        if (!items || !items.length) {
            return '<div class="mb-5"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div><div class="text-muted">Sin diferencias.</div></div>';
        }
        var header = campos.map(function (campo) {
            return "<th>" + escapeHtml(campo) + "</th>";
        }).join("");
        var filas = items.slice(0, 80).map(function (item) {
            return "<tr>" + campos.map(function (campo) {
                if (campo === "riesgo") {
                    return "<td>" + badgeRiesgo(item[campo]) + "</td>";
                }
                return "<td>" + escapeHtml(item[campo]) + "</td>";
            }).join("") + "</tr>";
        }).join("");
        var nota = items.length > 80 ? '<div class="text-muted fs-8 mt-2">Mostrando 80 de ' + items.length + " registros.</div>" : "";
        return '<div class="mb-6"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div>' +
            '<div class="table-responsive"><table class="table table-sm table-row-dashed align-middle">' +
            '<thead><tr class="text-muted text-uppercase fs-8">' + header + '</tr></thead><tbody>' + filas + "</tbody></table></div>" + nota + "</div>";
    }

    function badgeRiesgo(riesgo) {
        var clase = "badge-light-secondary";
        if (riesgo === "bajo") {
            clase = "badge-light-success";
        } else if (riesgo === "medio") {
            clase = "badge-light-warning";
        } else if (riesgo === "alto" || riesgo === "bloqueante") {
            clase = "badge-light-danger";
        } else if (riesgo === "revision") {
            clase = "badge-light-dark";
        }
        return '<span class="badge ' + clase + '">' + escapeHtml(riesgo || "revision") + "</span>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-02
     * Proposito: probar conexion de un ambiente desde UI sin exponer secretos.
     * Impacto: UI Migraciones BD; muestra solo metadatos read-only.
     */
    function probarAmbiente(alias) {
        var contenedor = document.getElementById("migbd_ambiente_prueba_resultado");
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = '<div class="py-4 text-muted">Probando ambiente...</div>';
        request("/migracionBd/ambiente_probar?alias=" + encodeURIComponent(alias)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible probar el ambiente");
            }
            var d = response.depurar || {};
            var ambiente = d.ambiente || {};
            var servidor = d.servidor || {};
            var totales = d.totales || {};
            contenedor.innerHTML = '<div class="alert alert-success mb-4">' + escapeHtml(response.mensaje || "") + "</div>" +
                '<div class="row g-4">' +
                resumenBox("Ambiente", ambiente.alias || alias, "primary") +
                resumenBox("Base", servidor.base_actual || ambiente.base || "", "info") +
                resumenBox("Tablas", totales.tablas || 0, "success") +
                resumenBox("Columnas", totales.columnas || 0, "dark") +
                resumenBox("Indices", totales.indices || 0, "warning") +
                resumenBox("FKs", totales.foraneas || 0, "danger") +
                "</div>" +
                '<div class="table-responsive mt-5"><table class="table table-sm table-row-dashed align-middle">' +
                '<tbody>' +
                '<tr><th class="text-muted w-175px">Host</th><td>' + escapeHtml(ambiente.host || "") + "</td></tr>" +
                '<tr><th class="text-muted">Usuario</th><td>' + escapeHtml(ambiente.usuario || "") + "</td></tr>" +
                '<tr><th class="text-muted">Version MySQL</th><td>' + escapeHtml(servidor.version_mysql || "") + "</td></tr>" +
                '<tr><th class="text-muted">Nota</th><td>' + escapeHtml(d.nota || "") + "</td></tr>" +
                "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: validar si el destino esta listo para comparar o crear paquetes.
     * Impacto: UI Migraciones BD; no expone secretos ni ejecuta cambios.
     */
    function preflightDestino() {
        var alias = destinoSeleccionado() || "productivo";
        var contenedor = document.getElementById("migbd_ambiente_prueba_resultado");
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = '<div class="py-4 text-muted">Validando destino...</div>';
        request("/migracionBd/destino_preflight?alias=" + encodeURIComponent(alias)).then(function (response) {
            var d = response.depurar || {};
            var ambiente = d.ambiente || {};
            var conexion = d.conexion || {};
            contenedor.innerHTML = '<div class="alert alert-' + (d.puede_comparar ? "success" : "warning") + ' mb-5">' +
                '<div class="fw-bold fs-5 mb-1">' + escapeHtml(response.mensaje || "") + "</div>" +
                '<div>' + escapeHtml(d.siguiente_paso || "") + "</div>" +
                "</div>" +
                '<div class="row g-4 mb-5">' +
                resumenBox("Alias", d.alias || alias, "primary") +
                resumenBox("Configurado", d.configurado ? "si" : "no", d.configurado ? "success" : "warning") +
                resumenBox("Completo", d.completo ? "si" : "no", d.completo ? "success" : "warning") +
                resumenBox("Conexion", conexion.ok ? "ok" : "pendiente", conexion.ok ? "success" : "warning") +
                "</div>" +
                renderListaSimple("Bloqueos destino", (d.bloqueos || []).map(function (item) { return {bloqueo: item}; }), ["bloqueo"]) +
                '<div class="table-responsive mt-5"><table class="table table-sm table-row-dashed align-middle"><tbody>' +
                '<tr><th class="text-muted w-175px">Host</th><td>' + escapeHtml(ambiente.host || "") + "</td></tr>" +
                '<tr><th class="text-muted">Base</th><td>' + escapeHtml(ambiente.base || "") + "</td></tr>" +
                '<tr><th class="text-muted">Usuario</th><td>' + escapeHtml(ambiente.usuario || "") + "</td></tr>" +
                '<tr><th class="text-muted">Mensaje conexion</th><td>' + escapeHtml(conexion.mensaje || "") + "</td></tr>" +
                "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: generar SQL de revision para diferencias basicas de esquema.
     * Impacto: UI Migraciones BD; conserva Fase 1 sin ejecucion real.
     */
    function generarSql() {
        var destino = destinoSeleccionado();
        var salida = document.getElementById("migbd_sql_resultado");
        salida.textContent = "Generando SQL dry-run...";
        activarTab("#migbd_tab_sql");
        request("/migracionBd/sql_dry_run?destino=" + encodeURIComponent(destino)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar SQL");
            }
            var sentencias = response.depurar.sentencias || [];
            if (!sentencias.length) {
                salida.textContent = "-- Sin sentencias generadas.";
                return;
            }
            salida.textContent = sentencias.map(function (item) {
                return "-- " + item.orden + " | " + item.tipo + " | " + item.tabla + " | riesgo " + item.riesgo + "\n" + item.sql;
            }).join("\n\n");
        }).catch(function (error) {
            salida.textContent = "-- " + (error.message || String(error));
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: crear paquete de revision a partir del destino y tablas seleccionadas.
     * Impacto: UI Migraciones BD; persiste solo si existen tablas SYS y nunca ejecuta SQL.
     */
    function crearPaqueteDryRun() {
        var destino = destinoSeleccionado();
        var salida = document.getElementById("migbd_sql_resultado");
        salida.textContent = "Creando paquete dry-run...";
        activarTab("#migbd_tab_sql");
        postRequest("/migracionBd/paquete_dry_run_crear", {
            destino: destino,
            tablas: JSON.stringify(tablasSeleccionadas())
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible crear paquete");
            }
            var depurar = response.depurar || {};
            var sentencias = depurar.sentencias || [];
            var resumen = depurar.resumen || {};
            var encabezado = [
                "-- " + response.mensaje,
                "-- codigo: " + (depurar.codigo || "temporal"),
                "-- persistido: " + (depurar.persistido ? "si" : "no"),
                "-- hash_plan: " + (depurar.hash_plan || ""),
                "-- sentencias: " + sentencias.length,
                "-- incluye_datos_solicitados: " + (resumen.incluye_datos ? "si" : "no"),
                "-- bloqueos: " + ((resumen.bloqueos || []).length)
            ].join("\n");
            salida.textContent = encabezado + "\n\n" + sentencias.map(function (item) {
                return "-- " + item.orden + " | " + item.tipo + " | " + item.tabla + " | riesgo " + item.riesgo + "\n" + item.sql;
            }).join("\n\n");
        }).catch(function (error) {
            salida.textContent = "-- " + (error.message || String(error));
        });
    }

    function copiarSql() {
        var salida = document.getElementById("migbd_sql_resultado");
        if (!salida || !navigator.clipboard) {
            return;
        }
        navigator.clipboard.writeText(salida.textContent || "").then(function () {
            if (window.Swal) {
                Swal.fire({text: "SQL copiado", icon: "success", confirmButtonText: "Aceptar"});
            }
        });
    }

    function descargarSql() {
        var salida = document.getElementById("migbd_sql_resultado");
        var contenido = salida ? (salida.textContent || "").trim() : "";
        if (!contenido || contenido === "Sin SQL generado." || contenido === "-- Sin sentencias generadas.") {
            return;
        }
        descargarTexto("migraciones_bd_dryrun_" + timestampArchivo() + ".sql", contenido, "application/sql");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: listar paquetes persistidos de migracion.
     * Impacto: UI Migraciones BD; solo lectura sobre esquema tecnico.
     */
    function cargarPaquetes() {
        var contenedor = document.getElementById("migbd_paquetes_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Consultando paquetes...</div>';
        activarTab("#migbd_tab_paquetes");
        request("/migracionBd/paquetes_listar?limite=50").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar paquetes");
            }
            var paquetes = response.depurar && response.depurar.paquetes ? response.depurar.paquetes : [];
            if (!paquetes.length) {
                contenedor.innerHTML = '<div class="text-muted py-8 text-center">Sin paquetes persistidos.</div>';
                return;
            }
            var filas = paquetes.map(function (item) {
                return "<tr>" +
                    "<td><div class=\"d-flex gap-2\"><button type=\"button\" class=\"btn btn-sm btn-light-primary migbd-usar-paquete\" data-codigo=\"" + escapeHtml(item.codigo) + "\">Usar</button>" +
                    "<button type=\"button\" class=\"btn btn-sm btn-light migbd-detalle-paquete\" data-codigo=\"" + escapeHtml(item.codigo) + "\">Detalle</button></div></td>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.codigo) + "</td>" +
                    "<td>" + badgeEstado(item.estatus) + "</td>" +
                    "<td>" + escapeHtml(item.ambiente_destino) + "</td>" +
                    "<td>" + escapeHtml(item.total_sentencias || 0) + "</td>" +
                    "<td>" + escapeHtml(item.sentencias_riesgo_alto || 0) + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.fecha_registro || "") + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.fecha_aplicacion || "") + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th></th><th>Codigo</th><th>Estatus</th><th>Destino</th><th>SQL</th><th>Riesgo alto</th><th>Creado</th><th>Aplicado</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function cargarEjecuciones() {
        var contenedor = document.getElementById("migbd_ejecuciones_resultado");
        contenedor.innerHTML = '<div class="py-8 text-center text-muted">Consultando ejecuciones...</div>';
        activarTab("#migbd_tab_ejecuciones");
        request("/migracionBd/ejecuciones_listar?limite=50").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar ejecuciones");
            }
            var ejecuciones = response.depurar && response.depurar.ejecuciones ? response.depurar.ejecuciones : [];
            if (!ejecuciones.length) {
                contenedor.innerHTML = '<div class="text-muted py-8 text-center">Sin ejecuciones registradas.</div>';
                return;
            }
            var filas = ejecuciones.map(function (item) {
                return "<tr>" +
                    "<td><button type=\"button\" class=\"btn btn-sm btn-light migbd-detalle-ejecucion\" data-id=\"" + escapeHtml(item.id_migracion_ejecucion) + "\">" + escapeHtml(item.id_migracion_ejecucion) + "</button></td>" +
                    "<td>" + escapeHtml(item.codigo || "") + "</td>" +
                    "<td>" + badgeEstado(item.estatus) + "</td>" +
                    "<td>" + escapeHtml(item.ambiente_destino || "") + "</td>" +
                    "<td>" + escapeHtml(item.total_detalles || 0) + "</td>" +
                    "<td>" + escapeHtml(item.detalles_error || 0) + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.fecha_inicio || "") + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.fecha_fin || "") + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.mensaje || "") + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th>ID</th><th>Paquete</th><th>Estatus</th><th>Destino</th><th>Detalle</th><th>Errores</th><th>Inicio</th><th>Fin</th><th>Mensaje</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function badgeEstado(estado) {
        var clase = "badge-light-secondary";
        if (estado === "borrador" || estado === "iniciada") {
            clase = "badge-light-info";
        } else if (estado === "revisado" || estado === "autorizado") {
            clase = "badge-light-primary";
        } else if (estado === "aplicado" || estado === "aplicada") {
            clase = "badge-light-success";
        } else if (estado === "fallido" || estado === "fallida") {
            clase = "badge-light-danger";
        } else if (estado === "cancelado") {
            clase = "badge-light-warning";
        }
        return '<span class="badge ' + clase + '">' + escapeHtml(estado || "") + "</span>";
    }

    function consultarDetallePaquete(codigo) {
        var contenedor = document.getElementById("migbd_paquete_detalle");
        contenedor.innerHTML = '<div class="text-muted">Consultando detalle de paquete...</div>';
        request("/migracionBd/paquete_consultar?codigo=" + encodeURIComponent(codigo)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar paquete");
            }
            contenedor.innerHTML = renderDetallePaquete(response.depurar || {});
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderDetallePaquete(d) {
        var paquete = d.paquete || {};
        var sentencias = d.sentencias || [];
        var tablas = d.tablas || [];
        var resumen = paquete.resumen || {};
        return '<div class="border rounded p-5">' +
            '<div class="d-flex justify-content-between align-items-center mb-4">' +
            '<div><div class="fw-bold fs-5">' + escapeHtml(paquete.codigo || "") + '</div><div class="text-muted">' + escapeHtml(paquete.ambiente_origen || "") + " -> " + escapeHtml(paquete.ambiente_destino || "") + "</div></div>" +
            badgeEstado(paquete.estatus || "") +
            "</div>" +
            '<div class="row g-3 mb-5">' +
            resumenBox("Sentencias", sentencias.length, "dark") +
            resumenBox("Tablas", tablas.length, "primary") +
            resumenBox("Hash", (paquete.hash_plan || "").slice(0, 12), "info") +
            resumenBox("Aplicado", paquete.fecha_aplicacion || "", "success") +
            "</div>" +
            renderResumenPaquete(resumen) +
            renderTablaPaquete(tablas) +
            '<div class="fw-bold mb-2">SQL persistido</div>' +
            "<pre class=\"bg-light rounded p-3 mh-500px overflow-auto\"><code>" + escapeHtml(sentencias.map(function (item) {
                return "-- " + item.orden + " | " + item.tipo + " | " + item.tabla + " | " + item.riesgo + "\n" + item.sql_texto;
            }).join("\n\n") || "Sin SQL.") + "</code></pre>" +
            "</div>";
    }

    function renderResumenPaquete(resumen) {
        if (!resumen || !Object.keys(resumen).length) {
            return "";
        }
        return '<div class="row g-4 mb-5">' +
            resumenBox("Datos solicitados", (resumen.tablas_con_datos || []).length, (resumen.tablas_con_datos || []).length ? "warning" : "success") +
            resumenBox("Bloqueos", (resumen.bloqueos || []).length, (resumen.bloqueos || []).length ? "danger" : "success") +
            resumenBox("Riesgo alto", ((resumen.riesgos || {}).alto || 0), "danger") +
            resumenBox("Riesgo bloq.", ((resumen.riesgos || {}).bloqueante || 0), "danger") +
            "</div>" +
            '<div class="row g-5 mb-5">' +
            '<div class="col-xl-6">' + renderMapaResumen("Politicas del paquete", resumen.politicas || {}) + "</div>" +
            '<div class="col-xl-6">' + renderMapaResumen("Riesgos del paquete", resumen.riesgos || {}) + "</div>" +
            "</div>" +
            renderListaSimple("Tablas con datos solicitados", resumen.tablas_con_datos || [], ["tabla", "politica", "llave_natural"]) +
            renderListaSimple("Bloqueos del paquete", (resumen.bloqueos || []).map(function (item) { return {bloqueo: item}; }), ["bloqueo"]);
    }

    function renderTablaPaquete(tablas) {
        if (!tablas || !tablas.length) {
            return "";
        }
        return renderListaSimple("Tablas registradas en paquete", tablas, ["tabla", "politica", "incluye_datos", "llave_natural"]);
    }

    function renderListaSimple(titulo, items, campos) {
        if (!items || !items.length) {
            return '<div class="mb-5"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div><div class="text-muted">Sin registros.</div></div>';
        }
        var header = campos.map(function (campo) {
            return "<th>" + escapeHtml(campo) + "</th>";
        }).join("");
        var filas = items.slice(0, 60).map(function (item) {
            return "<tr>" + campos.map(function (campo) {
                return "<td>" + escapeHtml(item[campo]) + "</td>";
            }).join("") + "</tr>";
        }).join("");
        return '<div class="mb-5"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div>' +
            '<div class="table-responsive"><table class="table table-sm table-row-dashed align-middle">' +
            '<thead><tr class="text-muted text-uppercase fs-8">' + header + '</tr></thead><tbody>' + filas + "</tbody></table></div></div>";
    }

    function consultarDetalleEjecucion(id) {
        var contenedor = document.getElementById("migbd_ejecucion_detalle");
        contenedor.innerHTML = '<div class="text-muted">Consultando detalle de ejecucion...</div>';
        request("/migracionBd/ejecucion_consultar?id=" + encodeURIComponent(id)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar ejecucion");
            }
            contenedor.innerHTML = renderDetalleEjecucion(response.depurar || {});
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderDetalleEjecucion(d) {
        var ejecucion = d.ejecucion || {};
        var detalles = d.detalles || [];
        var filas = detalles.map(function (item) {
            return "<tr>" +
                "<td>" + escapeHtml(item.orden) + "</td>" +
                "<td>" + escapeHtml(item.tabla || "") + "</td>" +
                "<td>" + badgeEstado(item.resultado || "") + "</td>" +
                "<td class=\"text-muted\">" + escapeHtml(item.mensaje || "") + "</td>" +
                "</tr>";
        }).join("");
        return '<div class="border rounded p-5">' +
            '<div class="d-flex justify-content-between align-items-center mb-4">' +
            '<div><div class="fw-bold fs-5">Ejecucion #' + escapeHtml(ejecucion.id_migracion_ejecucion || "") + '</div><div class="text-muted">' + escapeHtml(ejecucion.codigo || "") + " -> " + escapeHtml(ejecucion.ambiente_destino || "") + "</div></div>" +
            badgeEstado(ejecucion.estatus || "") +
            "</div>" +
            '<div class="table-responsive"><table class="table table-sm table-row-dashed align-middle">' +
            '<thead><tr class="text-muted text-uppercase fs-8"><th>Orden</th><th>Tabla</th><th>Resultado</th><th>Mensaje</th></tr></thead><tbody>' +
            filas + "</tbody></table></div>" +
            '<div class="fw-bold mt-5 mb-2">Detalle SQL</div>' +
            "<pre class=\"bg-light rounded p-3 mh-500px overflow-auto\"><code>" + escapeHtml(detalles.map(function (item) {
                return "-- " + item.orden + " | " + item.tabla + " | " + item.resultado + "\n" + (item.sql_texto || "");
            }).join("\n\n") || "Sin detalle.") + "</code></pre>" +
            "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-31
     * Proposito: mostrar preflight de activacion de esquema tecnico sin ejecutar comandos.
     * Impacto: UI Migraciones BD; prepara respaldo y autorizacion de DDL.
     */
    function cargarPreflightActivacion() {
        var input = document.getElementById("migbd_respaldo_ruta");
        var contenedor = document.getElementById("migbd_activacion_resultado");
        var respaldo = input ? input.value : "";
        contenedor.innerHTML = '<div class="text-muted">Validando preflight...</div>';
        request("/migracionBd/activacion_preflight?respaldo=" + encodeURIComponent(respaldo)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible validar");
            }
            var d = response.depurar || {};
            var respaldoInfo = d.respaldo || {};
            var esquema = d.esquema_tecnico || {};
            var faltantes = esquema.faltantes || [];
            var estadoRespaldo = respaldoInfo.ok
                ? '<span class="badge badge-light-success">Respaldo valido</span>'
                : '<span class="badge badge-light-warning">Respaldo pendiente</span>';
            var estadoEsquema = esquema.listo
                ? '<span class="badge badge-light-success">Esquema listo</span>'
                : '<span class="badge badge-light-info">Faltan ' + faltantes.length + " tablas</span>";
            var acciones = '<div class="d-flex flex-wrap gap-3 mb-5">' +
                '<button type="button" class="btn btn-sm btn-light-primary migbd-preparar-respaldo" data-ruta="' + escapeHtml(d.ruta_respaldo_sugerida || "") + '" data-token="' + escapeHtml(d.token_respaldo || "") + '" data-confirmacion="' + escapeHtml(d.texto_autorizacion_respaldo || "") + '">' +
                '<i class="bi bi-magic"></i> Preparar respaldo</button>' +
                '<button type="button" class="btn btn-sm btn-light-info migbd-preparar-schema" data-token="' + escapeHtml(d.token || "") + '" data-confirmacion="' + escapeHtml(d.texto_autorizacion || "") + '">' +
                '<i class="bi bi-magic"></i> Preparar esquema</button>' +
                "</div>";

            contenedor.innerHTML = '<div class="d-flex gap-3 mb-4">' + estadoRespaldo + estadoEsquema + "</div>" +
                acciones +
                renderPlanEsquema(d.plan_esquema || {}) +
                '<div class="fw-bold mb-2">Ruta sugerida</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.ruta_respaldo_sugerida || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Comando sugerido para respaldo</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.comando_respaldo_sugerido || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Texto de autorizacion respaldo</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.texto_autorizacion_respaldo || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Texto de autorizacion esquema</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.texto_autorizacion || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Validacion respaldo</div>' +
                renderValidacionRespaldo(respaldoInfo) +
                (faltantes.length ? '<div class="fw-bold mt-4 mb-2">Tablas tecnicas faltantes</div><div class="text-muted">' + escapeHtml(faltantes.join(", ")) + "</div>" : "");
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderValidacionRespaldo(info) {
        var items = [
            ["ok", info.ok ? "si" : "no"],
            ["existe", info.existe ? "si" : "no"],
            ["legible", info.legible ? "si" : "no"],
            ["tamano", info.tamano_bytes || 0],
            ["dentro repo", info.dentro_repo ? "si" : "no"],
            ["placeholder", info.placeholder ? "si" : "no"]
        ];
        return '<div class="row g-3">' + items.map(function (item) {
            return '<div class="col-md-4"><div class="border rounded p-3"><div class="text-muted fs-8 text-uppercase">' +
                escapeHtml(item[0]) + '</div><div class="fw-semibold">' + escapeHtml(item[1]) + "</div></div></div>";
        }).join("") + "</div>";
    }

    function renderPlanEsquema(depurar) {
        var plan = Array.isArray(depurar) ? depurar : (depurar.plan || []);
        var resumen = Array.isArray(depurar) ? resumenPlanEsquema(plan) : (depurar.resumen || resumenPlanEsquema(plan));
        return '<div class="row g-4 mb-5">' +
            resumenBox("Tablas plan", resumen.total || 0, "primary") +
            resumenBox("Pendientes", resumen.pendientes || 0, (resumen.pendientes || 0) ? "warning" : "success") +
            resumenBox("Existentes", resumen.existentes || 0, "info") +
            resumenBox("Errores", resumen.errores || 0, (resumen.errores || 0) ? "danger" : "success") +
            "</div>" +
            renderListaSimple("Tablas tecnicas del plan", (resumen.tablas || []).map(function (tabla) { return {tabla: tabla}; }), ["tabla"]) +
            renderListaSimple("Acciones del dry-run", plan.map(function (item) {
                var dep = item.depurar || {};
                return {
                    tipo: item.tipo || "",
                    mensaje: item.mensaje || "",
                    tabla: dep.tabla || tablaDesdeSql(dep.sql || ""),
                    ejecutado: dep.ejecutado ? "si" : "no"
                };
            }), ["tipo", "mensaje", "tabla", "ejecutado"]);
    }

    function resumenPlanEsquema(plan) {
        var resumen = {total: (plan || []).length, pendientes: 0, existentes: 0, ejecutadas: 0, errores: 0, tablas: [], sql: []};
        (plan || []).forEach(function (item) {
            var dep = item.depurar || {};
            if (item.error) {
                resumen.errores += 1;
            }
            var tabla = dep.tabla || tablaDesdeSql(dep.sql || "");
            if (tabla) {
                resumen.tablas.push(tabla);
            }
            if (dep.sql) {
                resumen.pendientes += 1;
                resumen.sql.push(dep.sql);
            } else if (dep.ejecutado) {
                resumen.ejecutadas += 1;
            } else {
                resumen.existentes += 1;
            }
        });
        resumen.tablas = resumen.tablas.filter(function (tabla, index, arr) {
            return arr.indexOf(tabla) === index;
        });
        return resumen;
    }

    function tablaDesdeSql(sql) {
        var match = String(sql || "").match(/CREATE\s+TABLE\s+`([^`]+)`/i);
        return match ? match[1] : "";
    }

    function prepararCamposRespaldo(boton) {
        var ruta = boton.getAttribute("data-ruta") || "";
        var token = boton.getAttribute("data-token") || "";
        var confirmacion = boton.getAttribute("data-confirmacion") || "";
        var inputRuta = document.getElementById("migbd_respaldo_ruta");
        var inputToken = document.getElementById("migbd_respaldo_token");
        var inputConfirmacion = document.getElementById("migbd_respaldo_confirmacion");
        if (inputRuta && ruta) {
            inputRuta.value = ruta;
        }
        if (inputToken && token) {
            inputToken.value = token;
        }
        if (inputConfirmacion && confirmacion) {
            inputConfirmacion.value = confirmacion;
        }
    }

    function prepararCamposSchema(boton) {
        var token = boton.getAttribute("data-token") || "";
        var confirmacion = boton.getAttribute("data-confirmacion") || "";
        var inputToken = document.getElementById("migbd_schema_token");
        var inputConfirmacion = document.getElementById("migbd_schema_confirmacion");
        if (inputToken && token) {
            inputToken.value = token;
        }
        if (inputConfirmacion && confirmacion) {
            inputConfirmacion.value = confirmacion;
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: validar respaldo seleccionado sin ejecutar respaldo ni DDL.
     * Impacto: UI Migraciones BD; permite confirmar si el archivo sirve como compuerta.
     */
    function validarRespaldoSeleccionado() {
        var input = document.getElementById("migbd_respaldo_ruta");
        var contenedor = document.getElementById("migbd_respaldos_resultado");
        var respaldo = input ? input.value : "";
        if (!contenedor) {
            return Promise.resolve();
        }
        contenedor.innerHTML = '<div class="text-muted">Validando respaldo seleccionado...</div>';
        return request("/migracionBd/respaldo_validar?respaldo=" + encodeURIComponent(respaldo)).then(function (response) {
            var d = response.depurar || {};
            contenedor.innerHTML = '<div class="alert alert-' + (d.ok ? "success" : "warning") + ' mb-4">' + escapeHtml(response.mensaje || "") + "</div>" +
                renderValidacionRespaldo(d) +
                '<div class="alert alert-info mt-4 mb-0">' + escapeHtml(d.ok ? "Respaldo listo para usar como compuerta de esquema/paquete." : "Selecciona un .sql legible, externo al repo y con tamano mayor a cero.") + "</div>";
            return response;
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: solicitar respaldo local con mysqldump desde la consola.
     * Impacto: UI Migraciones BD; no modifica BD, genera archivo externo si backend autoriza.
     */
    function generarRespaldoLocal() {
        var token = (document.getElementById("migbd_respaldo_token") || {}).value || "";
        var confirmacion = (document.getElementById("migbd_respaldo_confirmacion") || {}).value || "";
        var inputRuta = document.getElementById("migbd_respaldo_ruta");
        var contenedor = document.getElementById("migbd_activacion_resultado");
        var enviar = function () {
            contenedor.innerHTML = '<div class="text-muted">Generando respaldo local...</div>';
            postRequest("/migracionBd/respaldo_generar", {
                alcance: "migracion_bd",
                autorizar: token,
                confirmacion: confirmacion
            }).then(function (response) {
                var d = response.depurar || {};
                if (!response.error && d.archivo && inputRuta) {
                    inputRuta.value = d.archivo;
                }
                contenedor.innerHTML = '<div class="alert alert-' + (response.error ? "warning" : "success") + ' mb-4">' + escapeHtml(response.mensaje || "") + "</div>" +
                    '<div class="fw-bold mb-2">Resultado respaldo</div>' +
                    "<pre class=\"bg-light rounded p-3 mh-400px overflow-auto\"><code>" + escapeHtml(JSON.stringify(d, null, 2)) + "</code></pre>" +
                    (!response.error && d.archivo ? '<div class="alert alert-info mt-4 mb-0">Respaldo generado. Validalo y despues ejecuta Semaforo final o Dry-run esquema.</div>' : "");
                if (!response.error && d.archivo) {
                    validarRespaldoSeleccionado();
                }
            }).catch(function (error) {
                contenedor.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
            });
        };

        if (!window.Swal) {
            enviar();
            return;
        }
        Swal.fire({
            text: "Esto generara un archivo .sql local en la ruta estandar de respaldos.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Generar respaldo",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result.isConfirmed) {
                enviar();
            }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: validar compuertas finales del esquema tecnico sin ejecutar DDL.
     * Impacto: UI Migraciones BD; prepara la decision antes de aplicar sys_migraciones_*.
     */
    function preflightFinalEsquemaTecnico() {
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var token = (document.getElementById("migbd_schema_token") || {}).value || "";
        var confirmacion = (document.getElementById("migbd_schema_confirmacion") || {}).value || "";
        var contenedor = document.getElementById("migbd_activacion_resultado");
        contenedor.innerHTML = '<div class="text-muted">Validando compuertas del esquema...</div>';
        postRequest("/migracionBd/esquema_preflight_final", {
            respaldo: respaldo,
            autorizar: token,
            confirmacion: confirmacion
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible validar esquema");
            }
            contenedor.innerHTML = renderPreflightFinalEsquema(response.depurar || {});
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: verificar estado del esquema tecnico sin ejecutar DDL.
     * Impacto: UI Migraciones BD; confirma si sys_migraciones_* ya quedo listo.
     */
    function verificarEsquemaTecnico() {
        var contenedor = document.getElementById("migbd_activacion_resultado");
        contenedor.innerHTML = '<div class="text-muted">Verificando esquema tecnico...</div>';
        request("/migracionBd/esquema_verificar").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible verificar esquema");
            }
            var d = response.depurar || {};
            var esquema = d.esquema_tecnico || {};
            var faltantes = esquema.faltantes || [];
            contenedor.innerHTML = '<div class="alert alert-' + (d.listo ? "success" : "warning") + ' mb-5">' +
                '<div class="fw-bold fs-5 mb-1">' + escapeHtml(response.mensaje || "") + "</div>" +
                '<div>' + escapeHtml(d.siguiente_paso || "") + "</div>" +
                "</div>" +
                '<div class="row g-4 mb-5">' +
                resumenBox("Listo", d.listo ? "si" : "no", d.listo ? "success" : "warning") +
                resumenBox("Faltantes", faltantes.length, faltantes.length ? "danger" : "success") +
                resumenBox("Tablas", Object.keys(esquema.tablas || {}).length, "info") +
                resumenBox("Pendientes", (((d.plan_esquema || {}).resumen || {}).pendientes || 0), ((((d.plan_esquema || {}).resumen || {}).pendientes || 0) ? "warning" : "success")) +
                "</div>" +
                renderPlanEsquema(d.plan_esquema || {}) +
                renderListaSimple("Tablas faltantes", faltantes.map(function (tabla) { return {tabla: tabla}; }), ["tabla"]);
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderPreflightFinalEsquema(d) {
        var activacion = d.activacion || {};
        var color = d.puede_aplicar ? "success" : ((d.bloqueos || []).length ? "danger" : "info");
        var chips = [
            d.respaldo_ok ? '<span class="badge badge-light-success">Respaldo valido</span>' : '<span class="badge badge-light-warning">Respaldo pendiente</span>',
            d.token_ok ? '<span class="badge badge-light-success">Token ok</span>' : '<span class="badge badge-light-danger">Token pendiente</span>',
            d.confirmacion_ok ? '<span class="badge badge-light-success">Confirmacion ok</span>' : '<span class="badge badge-light-danger">Confirmacion pendiente</span>',
            d.plan_sin_errores ? '<span class="badge badge-light-success">Plan sin errores</span>' : '<span class="badge badge-light-danger">Plan con errores</span>',
            d.hay_pendientes ? '<span class="badge badge-light-warning">Hay DDL pendiente</span>' : '<span class="badge badge-light-info">Sin DDL pendiente</span>'
        ].join(" ");
        return '<div class="alert alert-' + color + ' mb-5">' +
            '<div class="fw-bold fs-5 mb-1">' + escapeHtml(d.puede_aplicar ? "Puede aplicar esquema tecnico" : "Esquema tecnico no listo para aplicar") + "</div>" +
            '<div>' + escapeHtml(d.siguiente_paso || "") + "</div>" +
            "</div>" +
            '<div class="d-flex gap-2 flex-wrap mb-5">' + chips + "</div>" +
            renderListaSimple("Bloqueos", (d.bloqueos || []).map(function (item) { return {bloqueo: item}; }), ["bloqueo"]) +
            renderListaSimple("Advertencias", (d.advertencias || []).map(function (item) { return {advertencia: item}; }), ["advertencia"]) +
            renderPlanEsquema(activacion.plan_esquema || {}) +
            '<div class="fw-bold mb-2">Validacion respaldo</div>' +
            renderValidacionRespaldo(activacion.respaldo || {});
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: mostrar plan read-only de restauracion de respaldo.
     * Impacto: UI Migraciones BD; no ejecuta restauracion.
     */
    function cargarPreflightRestauracion() {
        var input = document.getElementById("migbd_respaldo_ruta");
        var contenedor = document.getElementById("migbd_activacion_resultado");
        var respaldo = input ? input.value : "";
        contenedor.innerHTML = '<div class="text-muted">Preparando plan de restauracion...</div>';
        request("/migracionBd/restauracion_preflight?respaldo=" + encodeURIComponent(respaldo)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible preparar restauracion");
            }
            var d = response.depurar || {};
            contenedor.innerHTML = '<div class="d-flex gap-2 flex-wrap mb-4">' +
                (d.puede_restaurar ? '<span class="badge badge-light-success">Restauracion preparable</span>' : '<span class="badge badge-light-warning">Restauracion no lista</span>') +
                (d.mysql_disponible ? '<span class="badge badge-light-success">mysql.exe disponible</span>' : '<span class="badge badge-light-danger">mysql.exe no disponible</span>') +
                "</div>" +
                '<div class="fw-bold mb-2">Validacion respaldo</div>' +
                renderValidacionRespaldo(d.respaldo || {}) +
                '<div class="fw-bold mt-4 mb-2">Comando restore saneado</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.comando_restore_saneado || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Texto de autorizacion</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.texto_autorizacion || "") + "</code></pre>" +
                '<div class="alert alert-info mb-0">' + escapeHtml(d.nota || "") + "</div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-02
     * Proposito: listar respaldos SQL disponibles y permitir seleccionarlos.
     * Impacto: UI Migraciones BD; solo lectura de archivos reportados por backend.
     */
    function cargarRespaldos() {
        var contenedor = document.getElementById("migbd_respaldos_resultado");
        contenedor.innerHTML = '<div class="text-muted">Consultando respaldos...</div>';
        request("/migracionBd/respaldos_listar?limite=50").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar respaldos");
            }
            var respaldos = response.depurar && response.depurar.respaldos ? response.depurar.respaldos : [];
            if (!respaldos.length) {
                contenedor.innerHTML = '<div class="alert alert-info mb-0">No hay respaldos .sql en la carpeta configurada.</div>';
                return;
            }
            var filas = respaldos.map(function (item) {
                return "<tr>" +
                    "<td><button type=\"button\" class=\"btn btn-sm btn-light-primary migbd-usar-respaldo\" data-archivo=\"" + escapeHtml(item.archivo) + "\">Usar</button></td>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.nombre) + "</td>" +
                    "<td>" + escapeHtml(item.tamano_mb) + " MB</td>" +
                    "<td>" + (item.legible ? '<span class="badge badge-light-success">legible</span>' : '<span class="badge badge-light-warning">no legible</span>') + "</td>" +
                    "<td class=\"text-muted\">" + escapeHtml(item.fecha_modificacion) + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="table-responsive mt-4"><table class="table table-sm table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th></th><th>Archivo</th><th>Tamano</th><th>Estado</th><th>Fecha</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-31
     * Proposito: solicitar dry-run o aplicacion protegida del esquema tecnico sys_migraciones_*.
     * Impacto: UI Migraciones BD; la aplicacion real exige token, respaldo y confirmacion en backend.
     */
    function ejecutarEsquemaTecnico(ejecutar) {
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var token = (document.getElementById("migbd_schema_token") || {}).value || "";
        var confirmacion = (document.getElementById("migbd_schema_confirmacion") || {}).value || "";
        var contenedor = document.getElementById("migbd_activacion_resultado");
        var enviar = function () {
            contenedor.innerHTML = '<div class="text-muted">' + (ejecutar ? "Solicitando aplicacion protegida..." : "Generando dry-run...") + "</div>";
            return postRequest("/migracionBd/esquema_actualizar", {
                ejecutar: ejecutar ? "1" : "0",
                respaldo: respaldo,
                autorizar: token,
                confirmacion: confirmacion
            }).then(function (response) {
                var depurar = response.depurar || {};
                var plan = Array.isArray(depurar) ? depurar : (depurar.plan || []);
                var resumen = Array.isArray(depurar) ? resumenPlanEsquema(plan) : (depurar.resumen || resumenPlanEsquema(plan));
                if (response.error) {
                    contenedor.innerHTML = '<div class="alert alert-warning mb-4">' + escapeHtml(response.mensaje || "Operacion bloqueada") + "</div>" +
                        "<pre class=\"bg-light rounded p-3\"><code>" + escapeHtml(JSON.stringify(response.depurar || {}, null, 2)) + "</code></pre>";
                    return;
                }
                contenedor.innerHTML = '<div class="alert alert-' + (ejecutar ? "success" : "info") + ' mb-4">' + escapeHtml(response.mensaje) + "</div>" +
                    renderPlanEsquema(depurar) +
                    '<div class="fw-bold mb-2">SQL generado</div>' +
                    "<pre class=\"bg-light rounded p-3 mh-400px overflow-auto\"><code>" + escapeHtml((resumen.sql || []).join("\n\n") || "-- Sin SQL pendiente.") + "</code></pre>";
            }).catch(function (error) {
                contenedor.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
            });
        };

        if (!ejecutar || !window.Swal) {
            enviar();
            return;
        }
        Swal.fire({
            text: "Esto intentara aplicar DDL local si el respaldo, token y confirmacion son validos.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result.isConfirmed) {
                enviar();
            }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-01
     * Proposito: validar o solicitar aplicacion controlada de un paquete persistido.
     * Impacto: UI Migraciones BD; no evita compuertas del backend.
     */
    function preflightPaquete() {
        var codigo = (document.getElementById("migbd_paquete_codigo") || {}).value || "";
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var contenedor = document.getElementById("migbd_paquete_aplicacion_resultado");
        contenedor.innerHTML = '<div class="text-muted">Validando paquete...</div>';
        request("/migracionBd/paquete_preflight?codigo=" + encodeURIComponent(codigo) + "&respaldo=" + encodeURIComponent(respaldo)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible validar paquete");
            }
            contenedor.innerHTML = renderPreflightPaquete(response.depurar || {});
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: mostrar semaforo final de compuertas antes de autorizar o aplicar.
     * Impacto: UI Migraciones BD; solo lectura.
     */
    function preflightFinalSemaforo() {
        var codigo = (document.getElementById("migbd_paquete_codigo") || {}).value || "";
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var contenedor = document.getElementById("migbd_paquete_aplicacion_resultado");
        contenedor.innerHTML = '<div class="text-muted">Generando semaforo final...</div>';
        request("/migracionBd/preflight_final?codigo=" + encodeURIComponent(codigo) + "&respaldo=" + encodeURIComponent(respaldo)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar semaforo");
            }
            contenedor.innerHTML = renderPreflightFinal(response.depurar || {});
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderPreflightFinal(d) {
        var estado = d.estado || "pendiente";
        var color = estado === "puede_aplicar" ? "success" : (estado === "puede_autorizar" ? "primary" : (estado === "puede_preparar" ? "info" : (estado === "bloqueado" ? "danger" : "warning")));
        var pasos = d.pasos || [];
        var htmlPasos = pasos.map(function (paso) {
            return '<div class="d-flex align-items-start gap-4 border rounded p-4 mb-3">' +
                '<div class="badge badge-light-' + colorNivel(paso.nivel || "warning") + '">' + escapeHtml(paso.ok ? "ok" : "rev") + "</div>" +
                '<div class="flex-grow-1">' +
                '<div class="fw-bold">' + escapeHtml(paso.titulo || paso.codigo || "") + "</div>" +
                '<div class="text-gray-700">' + escapeHtml(paso.mensaje || "") + "</div>" +
                "</div>" +
                "</div>";
        }).join("");
        return '<div class="alert alert-' + color + ' mb-5">' +
            '<div class="fw-bold fs-5 mb-1">' + escapeHtml(etiquetaEstadoFinal(estado)) + "</div>" +
            '<div>' + escapeHtml(d.siguiente_paso || "") + "</div>" +
            "</div>" +
            '<div class="row g-4 mb-5">' +
            resumenBox("Preparar", d.puede_preparar ? "si" : "no", d.puede_preparar ? "success" : "warning") +
            resumenBox("Autorizar", d.puede_autorizar ? "si" : "no", d.puede_autorizar ? "success" : "warning") +
            resumenBox("Aplicar", d.puede_aplicar ? "si" : "no", d.puede_aplicar ? "success" : "danger") +
            resumenBox("App real", d.aplicacion_real_habilitada ? "on" : "off", d.aplicacion_real_habilitada ? "success" : "info") +
            "</div>" +
            renderListaSimple("Bloqueos", (d.bloqueos || []).map(function (item) { return {bloqueo: item}; }), ["bloqueo"]) +
            renderListaSimple("Advertencias", (d.advertencias || []).map(function (item) { return {advertencia: item}; }), ["advertencia"]) +
            '<div class="fw-bold mb-2">Pasos</div>' + htmlPasos;
    }

    function etiquetaEstadoFinal(estado) {
        if (estado === "puede_aplicar") {
            return "Puede aplicar";
        }
        if (estado === "puede_autorizar") {
            return "Puede autorizar";
        }
        if (estado === "puede_preparar") {
            return "Puede preparar";
        }
        if (estado === "bloqueado") {
            return "Bloqueado";
        }
        return "Pendiente";
    }

    function renderPreflightPaquete(d) {
        var paquete = d.paquete || {};
        var riesgos = d.riesgos || {};
        var respaldo = d.respaldo || {};
        var vigencia = d.vigencia || {};
        var sentencias = d.sentencias || [];
        var resumen = paquete.resumen || {};
        var chips = [
            respaldo.ok ? '<span class="badge badge-light-success">Respaldo valido</span>' : '<span class="badge badge-light-warning">Respaldo pendiente</span>',
            vigencia.ok ? '<span class="badge badge-light-success">Hash vigente</span>' : '<span class="badge badge-light-danger">Hash no vigente</span>',
            d.estatus_autorizado ? '<span class="badge badge-light-success">Paquete autorizado</span>' : '<span class="badge badge-light-warning">Paquete no autorizado</span>',
            d.aplicacion_real_habilitada ? '<span class="badge badge-light-success">Aplicacion habilitada</span>' : '<span class="badge badge-light-info">Aplicacion real apagada</span>',
            d.puede_aplicar ? '<span class="badge badge-light-success">Aplicable</span>' : '<span class="badge badge-light-warning">No aplicable aun</span>'
        ].join(" ");
        return '<div class="d-flex gap-2 flex-wrap mb-4">' + chips + "</div>" +
            '<div class="row g-3 mb-4">' +
            resumenBox("Paquete", paquete.codigo || "", "primary") +
            resumenBox("Destino", paquete.ambiente_destino || "", "info") +
            resumenBox("Estatus", paquete.estatus || "", "warning") +
            resumenBox("Sentencias", sentencias.length, "dark") +
            "</div>" +
            renderResumenPaquete(resumen) +
            '<div class="fw-bold mb-2">Riesgos</div>' +
            "<pre class=\"bg-light rounded p-3 mb-4\"><code>" + escapeHtml(JSON.stringify(riesgos, null, 2)) + "</code></pre>" +
            '<div class="fw-bold mb-2">Vigencia del paquete</div>' +
            "<pre class=\"bg-light rounded p-3 mb-4\"><code>" + escapeHtml(JSON.stringify(vigencia, null, 2)) + "</code></pre>" +
            '<div class="fw-bold mb-2">Texto para autorizar paquete</div>' +
            "<pre class=\"bg-light rounded p-3 mb-4\"><code>" + escapeHtml(d.texto_autorizacion_paquete || "") + "</code></pre>" +
            '<div class="fw-bold mb-2">Texto para aplicar paquete</div>' +
            "<pre class=\"bg-light rounded p-3 mb-4\"><code>" + escapeHtml(d.texto_autorizacion || "") + "</code></pre>" +
            '<div class="fw-bold mb-2">Primeras sentencias</div>' +
            "<pre class=\"bg-light rounded p-3 mh-300px overflow-auto\"><code>" + escapeHtml(sentencias.slice(0, 10).map(function (item) {
                return "-- " + item.orden + " | " + item.tipo + " | " + item.tabla + " | riesgo " + item.riesgo + "\n" + item.sql_texto;
            }).join("\n\n") || "Sin sentencias.") + "</code></pre>";
    }

    function autorizarPaquete() {
        var codigo = (document.getElementById("migbd_paquete_codigo") || {}).value || "";
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var token = (document.getElementById("migbd_paquete_token") || {}).value || "";
        var confirmacion = (document.getElementById("migbd_paquete_confirmacion") || {}).value || "";
        var contenedor = document.getElementById("migbd_paquete_aplicacion_resultado");
        var enviar = function () {
            contenedor.innerHTML = '<div class="text-muted">Solicitando autorizacion de paquete...</div>';
            postRequest("/migracionBd/paquete_autorizar", {
                codigo: codigo,
                respaldo: respaldo,
                autorizar: token,
                confirmacion: confirmacion
            }).then(function (response) {
                contenedor.innerHTML = '<div class="alert alert-' + (response.error ? "warning" : "success") + ' mb-4">' + escapeHtml(response.mensaje || "") + "</div>" +
                    "<pre class=\"bg-light rounded p-3 mh-400px overflow-auto\"><code>" + escapeHtml(JSON.stringify(response.depurar || {}, null, 2)) + "</code></pre>";
            }).catch(function (error) {
                contenedor.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
            });
        };

        if (!window.Swal) {
            enviar();
            return;
        }
        Swal.fire({
            text: "Esto autorizara el paquete para una aplicacion futura si el respaldo y la confirmacion son validos. No ejecuta SQL.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Autorizar paquete",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result.isConfirmed) {
                enviar();
            }
        });
    }

    function aplicarPaquete(ejecutar) {
        var codigo = (document.getElementById("migbd_paquete_codigo") || {}).value || "";
        var respaldo = (document.getElementById("migbd_respaldo_ruta") || {}).value || "";
        var token = (document.getElementById("migbd_paquete_token") || {}).value || "";
        var confirmacion = (document.getElementById("migbd_paquete_confirmacion") || {}).value || "";
        var contenedor = document.getElementById("migbd_paquete_aplicacion_resultado");
        var enviar = function () {
            contenedor.innerHTML = '<div class="text-muted">' + (ejecutar ? "Solicitando aplicacion protegida..." : "Simulando aplicacion...") + "</div>";
            postRequest("/migracionBd/paquete_aplicar", {
                codigo: codigo,
                respaldo: respaldo,
                autorizar: token,
                confirmacion: confirmacion,
                ejecutar: ejecutar ? "1" : "0"
            }).then(function (response) {
                var clase = response.error ? "warning" : (ejecutar ? "success" : "info");
                contenedor.innerHTML = '<div class="alert alert-' + clase + ' mb-4">' + escapeHtml(response.mensaje || "") + "</div>" +
                    "<pre class=\"bg-light rounded p-3 mh-400px overflow-auto\"><code>" + escapeHtml(JSON.stringify(response.depurar || {}, null, 2)) + "</code></pre>";
            }).catch(function (error) {
                contenedor.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || String(error)) + "</div>";
            });
        };

        if (!ejecutar || !window.Swal) {
            enviar();
            return;
        }
        Swal.fire({
            text: "Esto intentara aplicar el SQL persistido del paquete si todas las compuertas del backend son validas.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Solicitar aplicacion",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (result.isConfirmed) {
                enviar();
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        var btnPoliticas = document.getElementById("migbd_btn_clasificar");
        var btnSelfcheck = document.getElementById("migbd_btn_selfcheck");
        var btnChecklist = document.getElementById("migbd_btn_checklist");
        var btnPerfilDatos = document.getElementById("migbd_btn_perfil_datos");
        var btnOrden = document.getElementById("migbd_btn_orden");
        var btnResumenDecision = document.getElementById("migbd_btn_resumen_decision");
        var btnManifiesto = document.getElementById("migbd_btn_manifiesto");
        var btnComparar = document.getElementById("migbd_btn_comparar");
        var btnSql = document.getElementById("migbd_btn_sql");
        var btnGuardarPoliticas = document.getElementById("migbd_btn_guardar_politicas");
        var btnPaquete = document.getElementById("migbd_btn_paquete");
        var btnCopiar = document.getElementById("migbd_btn_copiar_sql");
        var btnCopiarManifiesto = document.getElementById("migbd_btn_copiar_manifiesto");
        var btnDescargarSql = document.getElementById("migbd_btn_descargar_sql");
        var btnDescargarManifiesto = document.getElementById("migbd_btn_descargar_manifiesto");
        var btnPaquetesListar = document.getElementById("migbd_btn_paquetes_listar");
        var btnEjecucionesListar = document.getElementById("migbd_btn_ejecuciones_listar");
        var contenedorPaquetes = document.getElementById("migbd_paquetes_resultado");
        var contenedorEjecuciones = document.getElementById("migbd_ejecuciones_resultado");
        var btnPreflight = document.getElementById("migbd_btn_preflight");
        var btnRespaldoValidar = document.getElementById("migbd_btn_respaldo_validar");
        var btnRespaldoGenerar = document.getElementById("migbd_btn_respaldo_generar");
        var btnRestorePreflight = document.getElementById("migbd_btn_restore_preflight");
        var btnRespaldosListar = document.getElementById("migbd_btn_respaldos_listar");
        var contenedorRespaldos = document.getElementById("migbd_respaldos_resultado");
        var contenedorActivacion = document.getElementById("migbd_activacion_resultado");
        var btnSchemaVerificar = document.getElementById("migbd_btn_schema_verificar");
        var btnSchemaPreflightFinal = document.getElementById("migbd_btn_schema_preflight_final");
        var btnSchemaDryRun = document.getElementById("migbd_btn_schema_dryrun");
        var btnSchemaAplicar = document.getElementById("migbd_btn_schema_aplicar");
        var btnPaquetePreflight = document.getElementById("migbd_btn_paquete_preflight");
        var btnPreflightFinal = document.getElementById("migbd_btn_preflight_final");
        var btnPaqueteAutorizar = document.getElementById("migbd_btn_paquete_autorizar");
        var btnPaqueteSimular = document.getElementById("migbd_btn_paquete_simular");
        var btnPaqueteAplicar = document.getElementById("migbd_btn_paquete_aplicar");
        var contenedorAmbientes = document.getElementById("migbd_tab_ambientes");
        var btnPreflightDestino = document.getElementById("migbd_btn_preflight_destino");
        if (btnPoliticas) {
            btnPoliticas.addEventListener("click", cargarPoliticas);
        }
        if (btnSelfcheck) {
            btnSelfcheck.addEventListener("click", cargarSelfcheck);
        }
        if (btnChecklist) {
            btnChecklist.addEventListener("click", cargarChecklistOperativo);
        }
        if (btnPerfilDatos) {
            btnPerfilDatos.addEventListener("click", cargarPerfilDatos);
        }
        if (btnOrden) {
            btnOrden.addEventListener("click", cargarOrdenMigracion);
        }
        if (btnResumenDecision) {
            btnResumenDecision.addEventListener("click", cargarResumenDecision);
        }
        if (btnManifiesto) {
            btnManifiesto.addEventListener("click", generarManifiesto);
        }
        if (btnComparar) {
            btnComparar.addEventListener("click", comparar);
        }
        if (btnSql) {
            btnSql.addEventListener("click", generarSql);
        }
        if (btnGuardarPoliticas) {
            btnGuardarPoliticas.addEventListener("click", guardarPoliticas);
        }
        if (btnPaquete) {
            btnPaquete.addEventListener("click", crearPaqueteDryRun);
        }
        if (btnCopiar) {
            btnCopiar.addEventListener("click", copiarSql);
        }
        if (btnCopiarManifiesto) {
            btnCopiarManifiesto.addEventListener("click", copiarManifiesto);
        }
        if (btnDescargarSql) {
            btnDescargarSql.addEventListener("click", descargarSql);
        }
        if (btnDescargarManifiesto) {
            btnDescargarManifiesto.addEventListener("click", descargarManifiesto);
        }
        if (btnPaquetesListar) {
            btnPaquetesListar.addEventListener("click", cargarPaquetes);
        }
        if (btnEjecucionesListar) {
            btnEjecucionesListar.addEventListener("click", cargarEjecuciones);
        }
        if (contenedorPaquetes) {
            contenedorPaquetes.addEventListener("click", function (event) {
                var botonDetalle = event.target.closest(".migbd-detalle-paquete");
                if (botonDetalle) {
                    consultarDetallePaquete(botonDetalle.getAttribute("data-codigo") || "");
                    return;
                }
                var botonUsar = event.target.closest(".migbd-usar-paquete");
                if (!botonUsar) {
                    return;
                }
                var inputCodigo = document.getElementById("migbd_paquete_codigo");
                if (inputCodigo) {
                    inputCodigo.value = botonUsar.getAttribute("data-codigo") || "";
                }
                activarTab("#migbd_tab_activacion");
            });
        }
        if (contenedorEjecuciones) {
            contenedorEjecuciones.addEventListener("click", function (event) {
                var boton = event.target.closest(".migbd-detalle-ejecucion");
                if (boton) {
                    consultarDetalleEjecucion(boton.getAttribute("data-id") || "");
                }
            });
        }
        if (btnPreflight) {
            btnPreflight.addEventListener("click", cargarPreflightActivacion);
        }
        if (btnRespaldoValidar) {
            btnRespaldoValidar.addEventListener("click", validarRespaldoSeleccionado);
        }
        if (btnRespaldoGenerar) {
            btnRespaldoGenerar.addEventListener("click", generarRespaldoLocal);
        }
        if (btnRestorePreflight) {
            btnRestorePreflight.addEventListener("click", cargarPreflightRestauracion);
        }
        if (btnRespaldosListar) {
            btnRespaldosListar.addEventListener("click", cargarRespaldos);
        }
        if (contenedorRespaldos) {
            contenedorRespaldos.addEventListener("click", function (event) {
                var boton = event.target.closest(".migbd-usar-respaldo");
                if (!boton) {
                    return;
                }
                var input = document.getElementById("migbd_respaldo_ruta");
                if (input) {
                    input.value = boton.getAttribute("data-archivo") || "";
                }
                validarRespaldoSeleccionado();
            });
        }
        if (contenedorActivacion) {
            contenedorActivacion.addEventListener("click", function (event) {
                var botonRespaldo = event.target.closest(".migbd-preparar-respaldo");
                if (botonRespaldo) {
                    prepararCamposRespaldo(botonRespaldo);
                    return;
                }
                var botonSchema = event.target.closest(".migbd-preparar-schema");
                if (botonSchema) {
                    prepararCamposSchema(botonSchema);
                }
            });
        }
        if (btnSchemaVerificar) {
            btnSchemaVerificar.addEventListener("click", verificarEsquemaTecnico);
        }
        if (btnSchemaPreflightFinal) {
            btnSchemaPreflightFinal.addEventListener("click", preflightFinalEsquemaTecnico);
        }
        if (btnSchemaDryRun) {
            btnSchemaDryRun.addEventListener("click", function () {
                ejecutarEsquemaTecnico(false);
            });
        }
        if (btnSchemaAplicar) {
            btnSchemaAplicar.addEventListener("click", function () {
                ejecutarEsquemaTecnico(true);
            });
        }
        if (btnPaquetePreflight) {
            btnPaquetePreflight.addEventListener("click", preflightPaquete);
        }
        if (btnPreflightFinal) {
            btnPreflightFinal.addEventListener("click", preflightFinalSemaforo);
        }
        if (btnPaqueteAutorizar) {
            btnPaqueteAutorizar.addEventListener("click", autorizarPaquete);
        }
        if (btnPaqueteSimular) {
            btnPaqueteSimular.addEventListener("click", function () {
                aplicarPaquete(false);
            });
        }
        if (btnPaqueteAplicar) {
            btnPaqueteAplicar.addEventListener("click", function () {
                aplicarPaquete(true);
            });
        }
        var contenedorPoliticas = document.getElementById("migbd_politicas_resultado");
        if (contenedorPoliticas) {
            contenedorPoliticas.addEventListener("click", function (event) {
                if (event.target.closest("#migbd_btn_seleccionar_datos")) {
                    seleccionarPoliticasConDatos();
                    return;
                }
                if (event.target.closest("#migbd_btn_limpiar_seleccion")) {
                    limpiarSeleccionPoliticas();
                    return;
                }
                if (event.target.closest("#migbd_btn_aplicar_bulk")) {
                    aplicarPoliticaMasiva();
                }
            });
        }
        if (contenedorAmbientes) {
            contenedorAmbientes.addEventListener("click", function (event) {
                var boton = event.target.closest(".migbd-probar-ambiente");
                if (boton) {
                    probarAmbiente(boton.getAttribute("data-alias") || "");
                }
            });
        }
        if (btnPreflightDestino) {
            btnPreflightDestino.addEventListener("click", preflightDestino);
        }
    });
})();
