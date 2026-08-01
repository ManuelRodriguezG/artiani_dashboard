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
            var filas = politicasActuales.map(function (item) {
                return "<tr>" +
                    '<td><div class="form-check form-check-sm form-check-custom form-check-solid">' +
                    '<input class="form-check-input migbd-tabla-check" type="checkbox" value="' + escapeHtml(item.tabla) + '">' +
                    "</div></td>" +
                    "<td class=\"fw-semibold\">" + escapeHtml(item.tabla) + "</td>" +
                    "<td>" + selectPolitica(item.politica, item.tabla) + "</td>" +
                    '<td><div class="form-check form-switch form-check-custom form-check-solid">' +
                    '<input class="form-check-input migbd-incluye-datos" type="checkbox" data-tabla="' + escapeHtml(item.tabla) + '"' + (item.incluye_datos ? " checked" : "") + ">" +
                    "</div></td>" +
                    "<td class=\"text-gray-700\">" + escapeHtml(item.motivo) + "</td>" +
                    "</tr>";
            }).join("");
            contenedor.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed align-middle">' +
                '<thead><tr class="text-muted text-uppercase fs-8"><th></th><th>Tabla</th><th>Politica</th><th>Datos</th><th>Motivo</th></tr></thead>' +
                "<tbody>" + filas + "</tbody></table></div>";
        }).catch(function (error) {
            contenedor.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message || String(error)) + "</div>";
        });
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
        return Object.keys(mapa).map(function (tabla) {
            return mapa[tabla];
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
            var html = '<div class="row g-4 mb-5">' +
                resumenBox("Tablas solo local", resumen.tablas_solo_origen || 0, "primary") +
                resumenBox("Tablas solo destino", resumen.tablas_solo_destino || 0, "warning") +
                resumenBox("Columnas faltantes", resumen.columnas_faltantes_destino || 0, "info") +
                resumenBox("Indices faltantes", resumen.indices_faltantes_destino || 0, "success") +
                resumenBox("FKs faltantes", resumen.foraneas_faltantes_destino || 0, "danger") +
                "</div>";
            html += renderLista("Tablas solo en local", comp.tablas_solo_origen, ["tabla", "filas_estimadas"]);
            html += renderLista("Columnas faltantes en destino", comp.columnas_faltantes_destino, ["tabla", "columna", "definicion"]);
            html += renderLista("Indices faltantes en destino", comp.indices_faltantes_destino, ["tabla", "indice", "definicion"]);
            html += renderLista("Llaves foraneas faltantes en destino", comp.foraneas_faltantes_destino, ["tabla", "restriccion", "tabla_referencia", "definicion"]);
            html += renderLista("Columnas diferentes", comp.columnas_diferentes, ["tabla", "columna"]);
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
                return "<td>" + escapeHtml(item[campo]) + "</td>";
            }).join("") + "</tr>";
        }).join("");
        var nota = items.length > 80 ? '<div class="text-muted fs-8 mt-2">Mostrando 80 de ' + items.length + " registros.</div>" : "";
        return '<div class="mb-6"><div class="fw-bold mb-2">' + escapeHtml(titulo) + '</div>' +
            '<div class="table-responsive"><table class="table table-sm table-row-dashed align-middle">' +
            '<thead><tr class="text-muted text-uppercase fs-8">' + header + '</tr></thead><tbody>' + filas + "</tbody></table></div>" + nota + "</div>";
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
            var encabezado = [
                "-- " + response.mensaje,
                "-- codigo: " + (depurar.codigo || "temporal"),
                "-- persistido: " + (depurar.persistido ? "si" : "no"),
                "-- hash_plan: " + (depurar.hash_plan || ""),
                "-- sentencias: " + sentencias.length
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

            contenedor.innerHTML = '<div class="d-flex gap-3 mb-4">' + estadoRespaldo + estadoEsquema + "</div>" +
                '<div class="fw-bold mb-2">Ruta sugerida</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.ruta_respaldo_sugerida || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Comando sugerido para respaldo</div>' +
                '<pre class="bg-light rounded p-3 mb-4"><code>' + escapeHtml(d.comando_respaldo_sugerido || "") + "</code></pre>" +
                '<div class="fw-bold mb-2">Texto de autorizacion</div>' +
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
                var plan = response.depurar || [];
                if (response.error) {
                    contenedor.innerHTML = '<div class="alert alert-warning mb-4">' + escapeHtml(response.mensaje || "Operacion bloqueada") + "</div>" +
                        "<pre class=\"bg-light rounded p-3\"><code>" + escapeHtml(JSON.stringify(response.depurar || {}, null, 2)) + "</code></pre>";
                    return;
                }
                contenedor.innerHTML = '<div class="alert alert-' + (ejecutar ? "success" : "info") + ' mb-4">' + escapeHtml(response.mensaje) + "</div>" +
                    '<div class="fw-bold mb-2">Plan</div>' +
                    "<pre class=\"bg-light rounded p-3 mh-400px overflow-auto\"><code>" + escapeHtml(JSON.stringify(plan, null, 2)) + "</code></pre>";
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

    document.addEventListener("DOMContentLoaded", function () {
        var btnPoliticas = document.getElementById("migbd_btn_clasificar");
        var btnPerfilDatos = document.getElementById("migbd_btn_perfil_datos");
        var btnOrden = document.getElementById("migbd_btn_orden");
        var btnResumenDecision = document.getElementById("migbd_btn_resumen_decision");
        var btnComparar = document.getElementById("migbd_btn_comparar");
        var btnSql = document.getElementById("migbd_btn_sql");
        var btnGuardarPoliticas = document.getElementById("migbd_btn_guardar_politicas");
        var btnPaquete = document.getElementById("migbd_btn_paquete");
        var btnCopiar = document.getElementById("migbd_btn_copiar_sql");
        var btnPreflight = document.getElementById("migbd_btn_preflight");
        var btnSchemaDryRun = document.getElementById("migbd_btn_schema_dryrun");
        var btnSchemaAplicar = document.getElementById("migbd_btn_schema_aplicar");
        if (btnPoliticas) {
            btnPoliticas.addEventListener("click", cargarPoliticas);
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
        if (btnPreflight) {
            btnPreflight.addEventListener("click", cargarPreflightActivacion);
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
    });
})();
