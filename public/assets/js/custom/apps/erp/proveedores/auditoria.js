"use strict";
(function () {
    function esc(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : value;
        return div.innerHTML;
    }

    function badgeSeveridad(severidad) {
        var clases = {
            info: "badge-light-primary",
            warning: "badge-light-warning",
            danger: "badge-light-danger",
            success: "badge-light-success"
        };
        return "<span class=\"badge " + (clases[severidad] || "badge-light") + "\">" + esc(severidad || "info") + "</span>";
    }

    function cardResumen(titulo, valor, icono, clase) {
        return "<div class=\"col-sm-6 col-xl-3\">" +
            "<div class=\"card h-100\"><div class=\"card-body d-flex align-items-center gap-4 py-5\">" +
            "<div class=\"symbol symbol-45px\"><span class=\"symbol-label " + clase + "\"><i class=\"bi " + icono + " fs-2\"></i></span></div>" +
            "<div><div class=\"text-muted fs-7\">" + esc(titulo) + "</div><div class=\"fw-bold fs-2\">" + esc(valor) + "</div></div>" +
            "</div></div></div>";
    }

    function cargar() {
        var resumen = document.getElementById("proveedores_auditoria_resumen");
        var hallazgos = document.getElementById("proveedores_auditoria_hallazgos");
        resumen.innerHTML = "<div class=\"col-12 text-muted\">Cargando auditoria...</div>";
        hallazgos.innerHTML = "";

        fetch("/proveedor/auditoria_dry_run_erp", {credentials: "same-origin"})
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible consultar la auditoria.");
                }
                render(response.depurar || {});
            })
            .catch(function (error) {
                resumen.innerHTML = "<div class=\"col-12\"><div class=\"alert alert-danger mb-0\">" + esc(error.message) + "</div></div>";
                hallazgos.innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-danger py-8\">" + esc(error.message) + "</td></tr>";
                document.getElementById("proveedores_auditoria_muestras").innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-danger py-8\">" + esc(error.message) + "</td></tr>";
            });
    }

    function cargarEsquema() {
        document.getElementById("proveedores_esquema_resumen").innerHTML = "<span class=\"text-muted\">Cargando esquema...</span>";
        document.getElementById("proveedores_esquema_tabla").innerHTML = "";

        fetch("/proveedor/esquema_auditar_erp", {credentials: "same-origin"})
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible consultar el esquema.");
                }
                renderEsquema(response.depurar || {});
            })
            .catch(function (error) {
                document.getElementById("proveedores_esquema_resumen").innerHTML = "<span class=\"text-danger\">" + esc(error.message) + "</span>";
                document.getElementById("proveedores_esquema_tabla").innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-danger py-8\">" + esc(error.message) + "</td></tr>";
            });
    }

    function render(data) {
        var hallazgos = data.hallazgos || {};
        var filas = Object.keys(hallazgos).map(function (clave) {
            var item = hallazgos[clave] || {};
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(clave.replace(/_/g, " ")) + "</div><div class=\"text-muted fs-8\">" + esc(item.descripcion || "") + "</div>" + (item.motivo ? "<div class=\"text-warning fs-8\">" + esc(item.motivo) + "</div>" : "") + "</td>" +
                "<td>" + badgeSeveridad(item.severidad || "info") + "</td>" +
                "<td><span class=\"badge " + (item.estado === "consultado" ? "badge-light-success" : "badge-light-warning") + "\">" + esc(item.estado || "pendiente") + "</span></td>" +
                "<td class=\"text-end fw-bold\">" + (item.conteo === null || item.conteo === undefined ? "-" : esc(item.conteo)) + "</td>" +
                "</tr>";
        });

        document.getElementById("proveedores_auditoria_hallazgos").innerHTML = filas.join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin hallazgos disponibles</td></tr>";
        renderResumen(hallazgos, data.sin_escrituras);
        renderTablas(data.tablas || {});
        renderLimitaciones(data.limitaciones_contrato || []);
        renderMuestras(combinarMuestras(data.muestras_listas_legacy || {}, data.muestras_hallazgos || {}));
        renderProductivoSql(data.productivo_sql || {});
        renderStagingMigracion(data.staging_migracion || {});
        renderPreflightMigracion(data.preflight_migracion || {});
        renderPermisosRoles(data.permisos_roles || {});
    }

    function renderEsquema(data) {
        var resumen = data.resumen || {};
        var auditoria = data.auditoria || {};
        document.getElementById("proveedores_esquema_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Tablas faltantes " + esc(resumen.tablas_faltantes || 0) + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Columnas faltantes " + esc(resumen.columnas_faltantes || 0) + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Indices faltantes " + esc(resumen.indices_faltantes || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary fs-7\">Sin escrituras " + (data.sin_escrituras ? "si" : "revisar") + "</span>";

        document.getElementById("proveedores_esquema_tabla").innerHTML = Object.keys(auditoria).map(function (tabla) {
            var item = auditoria[tabla] || {};
            var faltantes = item.faltantes || {};
            var columnas = faltantes.columnas || {};
            var indices = faltantes.indices || {};
            var indicesColumnas = faltantes.indices_columnas || {};
            var totalIndices = Object.keys(indices).length + Object.keys(indicesColumnas).length;
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(tabla) + "</div><div class=\"text-muted fs-8\">" + esc(item.descripcion || "") + "</div>" + (!item.existe ? "<div class=\"text-danger fs-8\">Tabla no existe</div>" : "") + "</td>" +
                "<td>" + badgeSeveridad(item.severidad === "ok" ? "success" : item.severidad || "info") + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(Object.keys(columnas).length) + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(totalIndices) + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin contrato de esquema reportado</td></tr>";
    }

    function renderResumen(hallazgos, sinEscrituras) {
        var total = Object.keys(hallazgos).length;
        var advertencias = Object.keys(hallazgos).filter(function (clave) {
            return hallazgos[clave] && hallazgos[clave].severidad === "warning" && Number(hallazgos[clave].conteo || 0) > 0;
        }).length;
        var omitidos = Object.keys(hallazgos).filter(function (clave) {
            return hallazgos[clave] && hallazgos[clave].estado === "omitido";
        }).length;
        var proveedores = hallazgos.proveedores_total ? hallazgos.proveedores_total.conteo : 0;

        document.getElementById("proveedores_auditoria_resumen").innerHTML =
            cardResumen("Proveedores legacy", proveedores == null ? "-" : proveedores, "bi-building", "bg-light-primary") +
            cardResumen("Revisiones", total, "bi-list-check", "bg-light-info") +
            cardResumen("Advertencias con datos", advertencias, "bi-exclamation-triangle", "bg-light-warning") +
            cardResumen("Dry-run", sinEscrituras ? "Solo lectura" : "Revisar", "bi-shield-check", sinEscrituras ? "bg-light-success" : "bg-light-danger") +
            (omitidos ? cardResumen("Omitidos", omitidos, "bi-dash-circle", "bg-light-warning") : "");
    }

    function renderTablas(tablas) {
        var html = Object.keys(tablas).map(function (tabla) {
            var existe = !!tablas[tabla];
            return "<span class=\"badge " + (existe ? "badge-light-success" : "badge-light-danger") + " fs-7\">" +
                "<i class=\"bi " + (existe ? "bi-check-circle" : "bi-x-circle") + " me-1\"></i>" + esc(tabla) +
                "</span>";
        }).join("");
        document.getElementById("proveedores_auditoria_tablas").innerHTML = html || "<span class=\"text-muted\">Sin tablas reportadas</span>";
    }

    function renderLimitaciones(limitaciones) {
        document.getElementById("proveedores_auditoria_limitaciones").innerHTML = limitaciones.map(function (texto) {
            return "<div class=\"d-flex gap-3\"><i class=\"bi bi-info-circle text-info fs-4\"></i><div class=\"text-gray-700\">" + esc(texto) + "</div></div>";
        }).join("") || "<span class=\"text-muted\">Sin limitaciones reportadas</span>";
    }

    function renderMuestras(muestras) {
        var filas = [];
        Object.keys(muestras).forEach(function (grupo) {
            (muestras[grupo] || []).forEach(function (item) {
                var lista = item.referencia || [item.proveedor, item.lista].filter(Boolean).join(" | ") || ("Lista " + (item.id_lista_proveedor || "-"));
                var sku = item.descripcion_muestra || [item.sku, item.nombre].filter(Boolean).join(" | ") || "-";
                var dato = item.renglones !== undefined ? (item.renglones + " renglones") : (item.costo !== undefined ? item.costo : (item.estatus || "-"));
                if (item.dato !== undefined && item.dato !== null && String(item.dato) !== "") {
                    dato = item.dato;
                }
                filas.push("<tr>" +
                    "<td><span class=\"badge badge-light-info\">" + esc(grupo.replace(/_/g, " ")) + "</span></td>" +
                    "<td><div class=\"fw-bold\">" + esc(lista) + "</div><span class=\"text-muted fs-8\">ID lista " + esc(item.id_lista_proveedor || "-") + "</span></td>" +
                    "<td>" + esc(sku) + "</td>" +
                    "<td class=\"text-end fw-bold\">" + esc(dato) + "</td>" +
                    "</tr>");
            });
        });
        document.getElementById("proveedores_auditoria_muestras").innerHTML = filas.join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin muestras disponibles</td></tr>";
    }

    function combinarMuestras() {
        var combinado = {};
        Array.prototype.slice.call(arguments).forEach(function (grupoMuestras) {
            Object.keys(grupoMuestras || {}).forEach(function (grupo) {
                combinado[grupo] = (combinado[grupo] || []).concat(grupoMuestras[grupo] || []);
            });
        });
        return combinado;
    }

    function renderProductivoSql(data) {
        var resumen = data.resumen || {};
        document.getElementById("proveedores_productivo_resumen").innerHTML =
            cardResumen("Proveedores SQL", resumen.proveedores || 0, "bi-building", "bg-light-primary") +
            cardResumen("Listas SQL", resumen.listas || 0, "bi-card-list", "bg-light-info") +
            cardResumen("Renglones SQL", resumen.renglones || 0, "bi-list-ul", "bg-light-success") +
            cardResumen("Sin codigo", resumen.renglones_sin_codigo || 0, "bi-exclamation-triangle", "bg-light-warning");

        var archivos = data.archivos || {};
        document.getElementById("proveedores_productivo_archivos").innerHTML = Object.keys(archivos).map(function (clave) {
            var item = archivos[clave] || {};
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(clave) + "</div><span class=\"text-muted fs-8\">" + esc(item.ruta || "-") + "</span></td>" +
                "<td><span class=\"badge " + (item.existe ? "badge-light-success" : "badge-light-danger") + "\">" + (item.existe ? "Existe" : "No existe") + "</span></td>" +
                "<td class=\"text-end fw-bold\">" + esc(formatoBytes(item.tamano || 0)) + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-8\">Sin archivos reportados</td></tr>";

        var filas = [];
        var muestras = data.muestras || {};
        (muestras.proveedores || []).forEach(function (item) {
            filas.push(productivoFila("proveedor", item.id_proveedor, item.proveedor, item.cuota));
        });
        (muestras.listas || []).forEach(function (item) {
            filas.push(productivoFila("lista", item.id_lista_proveedor, "Proveedor " + (item.id_proveedor || "-") + " | " + (item.lista || "-"), item.fecha || item.estatus || "-"));
        });
        (muestras.renglones || []).forEach(function (item) {
            filas.push(productivoFila("renglon", item.id_producto, [item.sku, item.nombre].filter(Boolean).join(" | "), "$" + Number(item.costo || 0).toFixed(2)));
        });
        document.getElementById("proveedores_productivo_muestras").innerHTML = filas.join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin muestras disponibles</td></tr>";

        document.getElementById("proveedores_productivo_advertencias").innerHTML = (data.advertencias || []).map(function (texto) {
            return "<div class=\"alert alert-warning py-3 mb-3\">" + esc(texto) + "</div>";
        }).join("");
        renderPreviewMigracionProductivo(data.preview_migracion || {});
    }

    function renderPreviewMigracionProductivo(preview) {
        var resumen = preview.resumen || {};
        document.getElementById("proveedores_productivo_preview_resumen").innerHTML =
            cardResumen("Prov. crear", resumen.proveedores_crear || 0, "bi-plus-circle", "bg-light-success") +
            cardResumen("Prov. revisar", resumen.proveedores_revisar || 0, "bi-search", "bg-light-warning") +
            cardResumen("Listas crear", resumen.listas_crear || 0, "bi-card-list", "bg-light-info") +
            cardResumen("Renglones crear", resumen.renglones_crear || 0, "bi-list-check", "bg-light-primary") +
            cardResumen("Renglones revisar", resumen.renglones_revisar || 0, "bi-exclamation-triangle", "bg-light-warning") +
            cardResumen("Match SKU exacto", resumen.renglones_con_match_sku || 0, "bi-link-45deg", "bg-light-success");

        var filas = [];
        var muestras = preview.muestras || {};
        (muestras.proveedores || []).forEach(function (item) {
            filas.push(previewFila("proveedor", item.id_origen, item.referencia, item.accion));
        });
        (muestras.listas || []).forEach(function (item) {
            filas.push(previewFila("lista", item.id_origen, item.referencia, item.accion));
        });
        (muestras.renglones || []).forEach(function (item) {
            var ref = [item.referencia, item.descripcion, "match SKU: " + item.match_sku, "$" + Number(item.costo || 0).toFixed(2)].filter(Boolean).join(" | ");
            filas.push(previewFila("renglon", item.id_origen, ref, item.accion));
        });
        document.getElementById("proveedores_productivo_preview_muestras").innerHTML = filas.join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin vista previa disponible</td></tr>";
    }

    function previewFila(tipo, origen, referencia, accion) {
        var accionTexto = String(accion || "revisar").replace(/_/g, " ");
        var clase = accion === "crear" || accion === "crear_borrador" || accion === "crear_evidencia" ? "badge-light-success" :
            (accion === "existente" || accion === "conservar_actualizar" ? "badge-light-primary" : "badge-light-warning");
        return "<tr>" +
            "<td><span class=\"badge badge-light-info\">" + esc(tipo) + "</span></td>" +
            "<td class=\"fw-bold\">" + esc(origen || "-") + "</td>" +
            "<td>" + esc(referencia || "-") + "</td>" +
            "<td class=\"text-end\"><span class=\"badge " + clase + "\">" + esc(accionTexto) + "</span></td>" +
            "</tr>";
    }

    function productivoFila(grupo, referencia, descripcion, dato) {
        return "<tr>" +
            "<td><span class=\"badge badge-light-info\">" + esc(grupo) + "</span></td>" +
            "<td class=\"fw-bold\">" + esc(referencia || "-") + "</td>" +
            "<td>" + esc(descripcion || "-") + "</td>" +
            "<td class=\"text-end fw-bold\">" + esc(dato == null ? "-" : dato) + "</td>" +
            "</tr>";
    }

    function formatoBytes(bytes) {
        bytes = Number(bytes || 0);
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + " MB";
        }
        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + " KB";
        }
        return bytes + " B";
    }

    function renderStagingMigracion(data) {
        document.getElementById("proveedores_staging_lotes").innerHTML = (data.lotes || []).map(function (item) {
            return "<tr>" +
                "<td class=\"fw-bold\">" + esc(item.lote || "-") + "</td>" +
                "<td>" + esc(item.ultima_fecha || item.primera_fecha || "-") + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(item.total || 0) + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin lotes staging cargados</td></tr>";

        document.getElementById("proveedores_staging_resumen").innerHTML = (data.resumen || []).map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(item.lote || "-") + "</div><span class=\"text-muted fs-8\">" + esc(item.tipo_registro || "-") + "</span></td>" +
                "<td><span class=\"badge badge-light-primary\">" + esc(String(item.accion_propuesta || "-").replace(/_/g, " ")) + "</span><div class=\"text-muted fs-8\">" + esc(String(item.estado_revision || "-").replace(/_/g, " ")) + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + esc(item.total || 0) + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin resumen staging</td></tr>";

        document.getElementById("proveedores_staging_revision").innerHTML = (data.muestras_revision || []).map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(item.tipo_registro || "-") + " " + esc(item.id_origen || "-") + "</div><span class=\"text-muted fs-8\">" + esc(item.lote || "-") + "</span></td>" +
                "<td>" + esc(item.referencia || "-") + "</td>" +
                "<td class=\"text-warning\">" + esc(item.motivo_revision || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin registros a revisar</td></tr>";
    }

    function renderPreflightMigracion(data) {
        var resumenContenedor = document.getElementById("proveedores_preflight_resumen");
        var riesgosContenedor = document.getElementById("proveedores_preflight_riesgos");
        var pasosContenedor = document.getElementById("proveedores_preflight_pasos");
        if (!resumenContenedor || !riesgosContenedor || !pasosContenedor) {
            return;
        }

        var resumen = data.resumen || {};
        var estado = data.estado || "sin_datos";
        var estadoClase = estado === "preparado_para_autorizacion" ? "bg-light-success" :
            (estado === "sin_candidatos_nuevos" ? "bg-light-info" : "bg-light-warning");
        resumenContenedor.innerHTML =
            cardResumen("Estado", String(estado).replace(/_/g, " "), "bi-clipboard-check", estadoClase) +
            cardResumen("Candidatos", resumen.candidatos_nuevos || 0, "bi-plus-circle", "bg-light-success") +
            cardResumen("Existentes", resumen.ya_migrados_o_existentes || 0, "bi-archive", "bg-light-info") +
            cardResumen("Revision", resumen.requieren_revision || 0, "bi-exclamation-triangle", Number(resumen.requieren_revision || 0) > 0 ? "bg-light-warning" : "bg-light-success");

        riesgosContenedor.innerHTML = (data.riesgos || []).map(function (texto) {
            return "<div class=\"alert alert-warning py-3 mb-0\">" + esc(texto) + "</div>";
        }).join("") || "<div class=\"alert alert-success py-3 mb-0\">Sin riesgos adicionales detectados en el preflight.</div>";

        pasosContenedor.innerHTML = (data.pasos || []).map(function (paso) {
            var estadoPaso = paso.estado || "pendiente";
            var clase = estadoPaso === "listo" ? "badge-light-success" :
                (estadoPaso === "bloqueado" ? "badge-light-danger" :
                    (estadoPaso === "requiere_autorizacion" ? "badge-light-warning" : "badge-light-info"));
            return "<div class=\"border rounded p-4\">" +
                "<div class=\"d-flex justify-content-between gap-3 mb-1\">" +
                    "<div class=\"fw-bold\">" + esc(paso.paso || "-") + "</div>" +
                    "<span class=\"badge " + clase + "\">" + esc(String(estadoPaso).replace(/_/g, " ")) + "</span>" +
                "</div>" +
                "<div class=\"text-muted fs-7\">" + esc(paso.detalle || "") + "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-muted\">Sin pasos reportados.</div>";
    }

    function renderPermisosRoles(data) {
        var contenedor = document.getElementById("proveedores_permisos_resumen");
        var tabla = document.getElementById("proveedores_permisos_roles");
        if (!contenedor || !tabla) {
            return;
        }
        if (!data.disponible) {
            contenedor.innerHTML = "<span class=\"badge badge-light-warning fs-7\">No disponible</span>";
            tabla.innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">" + esc(data.motivo || "No se pudo auditar permisos.") + "</td></tr>";
            return;
        }

        var resumen = data.resumen || {};
        contenedor.innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Permisos base " + esc(resumen.permisos_base || 0) + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Permisos BD " + esc(resumen.permisos_bd || 0) + "</span>" +
            "<span class=\"badge " + (Number(resumen.permisos_faltantes_bd || 0) > 0 ? "badge-light-danger" : "badge-light-success") + " fs-7\">Faltantes BD " + esc(resumen.permisos_faltantes_bd || 0) + "</span>" +
            "<span class=\"badge " + (Number(resumen.roles_con_faltantes || 0) > 0 ? "badge-light-warning" : "badge-light-success") + " fs-7\">Roles con faltantes " + esc(resumen.roles_con_faltantes || 0) + "</span>" +
            "<span class=\"badge " + (Number(resumen.roles_con_permisos_extra || 0) > 0 ? "badge-light-warning" : "badge-light-success") + " fs-7\">Roles con extras " + esc(resumen.roles_con_permisos_extra || 0) + "</span>";

        tabla.innerHTML = (data.roles || []).map(function (rol) {
            var asignados = rol.permisos_asignados || [];
            var esperados = rol.permisos_esperados || [];
            var faltantes = rol.faltantes || [];
            var extras = rol.sobrantes || [];
            var sensibles = asignados.filter(function (permiso) {
                return ["proveedores.documentos_sensibles", "proveedores.autorizar", "proveedores.costos"].indexOf(permiso) >= 0;
            });
            var revision = [];
            if (faltantes.length) {
                revision.push("<div class=\"text-warning fs-8\">Faltan: " + esc(faltantes.join(", ")) + "</div>");
            }
            if (extras.length) {
                revision.push("<div class=\"text-info fs-8\">Extras vs base: " + esc(extras.join(", ")) + "</div>");
            }
            if (sensibles.length) {
                revision.push("<div class=\"text-muted fs-8\">Sensibles: " + esc(sensibles.join(", ")) + "</div>");
            }
            if (!revision.length) {
                revision.push("<span class=\"badge badge-light-success\">Alineado</span>");
            }
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(rol.rol || "-") + "</div><span class=\"text-muted fs-8\">" + esc(rol.descripcion || "") + "</span></td>" +
                "<td class=\"text-end fw-bold\">" + esc(asignados.length) + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(esperados.length) + "</td>" +
                "<td>" + revision.join("") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin roles reportados</td></tr>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("proveedores_auditoria_recargar").addEventListener("click", cargar);
        document.getElementById("proveedores_esquema_recargar").addEventListener("click", cargarEsquema);
        cargar();
        cargarEsquema();
    });
})();
