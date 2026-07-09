"use strict";
(function () {
    var items = [];
    var timer;
    var modo = "editar";
    var puedeEditar = true;
    var permisos = {aprobar: false, cancelar: false, editar: true, editarAccion: false, crear: false};

    function esc(value) {
        var d = document.createElement("div");
        d.textContent = value == null ? "" : value;
        return d.innerHTML;
    }

    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: new URLSearchParams(data),
            credentials: "same-origin"
        }).then(function (r) {
            return r.json();
        });
    }

    function leerPermisos() {
        permisos.aprobar = Number(document.getElementById("solicitud_permiso_aprobar").value || 0) === 1;
        permisos.cancelar = Number(document.getElementById("solicitud_permiso_cancelar").value || 0) === 1;
        permisos.editar = Number(document.getElementById("solicitud_permiso_editar").value || 0) === 1 && modo === "editar";
        permisos.editarAccion = Number(document.getElementById("solicitud_permiso_editar_accion").value || 0) === 1;
        permisos.crear = Number(document.getElementById("solicitud_permiso_crear").value || 0) === 1;
    }

    function cargarCatalogos() {
        return fetch("/compra/solicitudes_catalogos_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                document.getElementById("solicitud_proveedor").innerHTML =
                    "<option value=\"\">Seleccionar</option>" +
                    (r.depurar.proveedores || []).map(function (x) {
                        return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
                    }).join("");
                document.getElementById("solicitud_almacen").innerHTML =
                    "<option value=\"\">Seleccionar</option>" +
                    (r.depurar.almacenes || []).map(function (x) {
                        return "<option value=\"" + esc(x.id_almacen) + "\">" + esc(x.almacen) + "</option>";
                    }).join("");
            });
    }

    function claveItem(item) {
        if (Number(item.id_sku_erp) > 0) {
            return "sku:" + Number(item.id_sku_erp);
        }
        return "sugerido:" + String((item.sku || "").trim().toLowerCase()) + "|" + String((item.nombre || "").trim().toLowerCase());
    }

    function existeItem(item) {
        var clave = claveItem(item);
        return items.some(function (x) {
            return claveItem(x) === clave;
        });
    }

    function tipoBadge(item) {
        if (item.es_nuevo) {
            return "<span class=\"badge badge-light-warning\">Sugerido</span> ";
        }
        return "";
    }

    function advertenciasOperativasHtml(item) {
        var advertencias = Array.isArray(item && item.advertencias_operativas) ? item.advertencias_operativas : [];
        if (!advertencias.length) {
            return "";
        }
        return "<div class=\"mt-1\">" + advertencias.map(function (x) {
            var clase = x.nivel === "info" ? "badge-light-info" : "badge-light-warning";
            return "<span class=\"badge " + clase + " me-1 mb-1\">" + esc(x.mensaje || x.codigo || "Revision") + "</span>";
        }).join("") + "</div>";
    }

    function evidenciaCostoHtml(item) {
        if (!item) {
            return "";
        }
        var fuente = String(item.fuente_costo || "");
        var moneda = item.moneda_costo || "MXN";
        var origen = item.origen_costo || "";
        var vigencia = [item.vigencia_desde, item.vigencia_hasta].filter(Boolean).join(" a ");
        var texto = fuente === "historial_vigente" ? "Costo vigente" : "Costo ultimo";
        var clase = fuente === "historial_vigente" ? "badge-light-success" : "badge-light-info";
        var detalle = [moneda, origen, vigencia].filter(Boolean).join(" | ");
        return "<div class=\"mt-1\"><span class=\"badge " + clase + " me-1\">" + esc(texto) + "</span>" +
            (detalle ? "<span class=\"text-muted fs-8\">" + esc(detalle) + "</span>" : "") + "</div>";
    }

    function renderizarDiferenciasFila(items, template) {
        if (!Array.isArray(items) || !items.length) {
            return "";
        }
        return items.map(template).join("");
    }
    function renderFilaDiferenciaSimple(x) {
        return "<tr><td>" + esc(x.sku || "-") + "</td>" +
            "<td>" + esc(x.nombre || "") + "</td>" +
            "<td class=\"text-end\">" + Number(x.cantidad || 0).toFixed(6) + "</td>" +
            "<td class=\"text-end\">$" + Number(x.costo_unitario || 0).toFixed(2) + "</td></tr>";
    }
    function renderFilaDiferenciaCambio(x) {
        return "<tr><td>" + esc(x.sku || "-") + "</td>" +
            "<td>" + esc(x.nombre || "") + "</td>" +
            "<td class=\"text-end\">" + Number(x.delta && x.delta.cantidad !== undefined ? x.delta.cantidad : 0).toFixed(6) + "</td>" +
            "<td class=\"text-end\">$" + Number(x.delta && x.delta.costo_unitario !== undefined ? x.delta.costo_unitario : 0).toFixed(6) + "</td></tr>";
    }

    function normalizarCantidadPorUnidad(unidad) {
        var unidadTexto = String(unidad || "").toLowerCase();
        if (!unidadTexto) {
            return 1;
        }
        if (/\b(kg|kgr|kgs|kilogr|lb|gram|g|ml|lts?|lt|l|m|cm|mm|metro|m2|metro cuadr|pie|yd|km|m3|cm3)\b/.test(unidadTexto)) {
            return 0.001;
        }
        return 1;
    }

    function cargarSolicitud() {
        var id = Number(document.getElementById("solicitud_id").value || 0);
        if (!id) {
            return Promise.resolve();
        }

        return fetch("/compra/solicitud_consultar_erp?id_solicitud=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) {
                    throw new Error(r.mensaje);
                }
                var s = r.depurar.solicitud;
                var ordenRelacionada = r.depurar.orden_relacionada || null;
                document.getElementById("solicitud_titulo").textContent = s.folio;
                document.getElementById("solicitud_estado_texto").textContent = s.estatus;
                document.getElementById("solicitud_proveedor").value = s.id_proveedor;
                document.getElementById("solicitud_almacen").value = s.id_almacen_destino || "";
                document.getElementById("solicitud_solicitante").value = s.solicitante_nombre || "Usuario actual";
                document.getElementById("solicitud_prioridad").value = s.prioridad || "normal";
                document.getElementById("solicitud_fecha_requerida").value = s.fecha_requerida || "";
                document.getElementById("solicitud_observaciones").value = s.observaciones || "";
                items = (r.depurar.detalle || []).map(function (x) {
                    return {
                        id_sku_erp: Number(x.id_sku_erp),
                        sku: x.sku,
                        nombre: x.nombre,
                        unidad: x.unidad,
                        sku_proveedor: x.sku_proveedor,
                        cantidad: Number(x.cantidad),
                        costo_estimado: Number(x.costo_estimado),
                        observaciones: x.observaciones || "",
                        es_nuevo: Number(x.id_sku_erp) <= 0
                    };
                });

                var puedeAprobar = s.estatus === "pendiente";
                var puedeRechazar = s.estatus === "pendiente";
                var puedeCancelar = (s.estatus === "borrador" || s.estatus === "pendiente" || s.estatus === "aprobada");
                var mostrarAprobacion = puedeAprobar || puedeRechazar || puedeCancelar;
                document.getElementById("solicitud_aprobacion").classList.toggle("d-none", !mostrarAprobacion);
                document.querySelector('[data-solicitud-estatus="aprobada"]').classList.toggle("d-none", !(puedeAprobar && permisos.aprobar));
                document.querySelector('[data-solicitud-estatus="rechazada"]').classList.toggle("d-none", !(puedeRechazar && permisos.aprobar));
                document.querySelector('[data-solicitud-estatus="cancelada"]').classList.toggle("d-none", !(puedeCancelar && permisos.cancelar));

                var botonEditar = document.getElementById("solicitud_editar_link");
                if (botonEditar) {
                    botonEditar.classList.toggle("d-none", !(modo === "ver" && permisos.editarAccion && s.estatus === "borrador"));
                }

                var habilitarGenerarOrden = s.estatus === "aprobada" && permisos.crear && !ordenRelacionada;
                document.getElementById("solicitud_generar_orden").classList.toggle("d-none", !habilitarGenerarOrden);
                renderOrdenRelacionada(ordenRelacionada);

                puedeEditar = permisos.editar && s.estatus === "borrador";
                if (s.estatus === "borrador") {
                    document.getElementById("solicitud_enviar").classList.toggle("d-none", !permisos.editar);
                    document.getElementById("solicitud_guardar_borrador").classList.toggle("d-none", !permisos.editar);
                } else {
                    document.getElementById("solicitud_enviar").classList.add("d-none");
                    document.getElementById("solicitud_guardar_borrador").classList.add("d-none");
                }

                if (!puedeEditar) {
                    deshabilitar();
                }
                document.getElementById("solicitud_buscar_sku").disabled = !puedeEditar || !document.getElementById("solicitud_proveedor").value;
                render();
                var botonDiferencias = document.getElementById("solicitud_ver_diferencias");
                if (botonDiferencias) {
                    botonDiferencias.classList.add("d-none");
                    cargarDiferenciasSolicitud().then(function () {
                        botonDiferencias.classList.remove("d-none");
                    }).catch(function () {
                        botonDiferencias.classList.add("d-none");
                    });
                }
            });
    }

    function renderOrdenRelacionada(orden) {
        var seccion = document.getElementById("solicitud_orden_relacionada");
        if (!seccion) {
            return;
        }
        seccion.classList.toggle("d-none", !orden);
        if (!orden) {
            document.getElementById("solicitud_orden_body").innerHTML = "";
            return;
        }
        document.getElementById("solicitud_orden_link").href = "/compra/ver_orden_compra/" + encodeURIComponent(orden.id_orden_compra);
        document.getElementById("solicitud_orden_body").innerHTML = "<tr>" +
            "<td><div class=\"fw-bold\">" + esc(orden.folio || "-") + "</div><div class=\"text-muted fs-8\">" + esc(orden.folio_proveedor || "") + "</div></td>" +
            "<td>" + esc(orden.proveedor || "") + "</td>" +
            "<td>" + esc(orden.fecha_orden || "-") + "</td>" +
            "<td class=\"text-end\">" + Number(orden.total_partidas || 0).toFixed(0) + "</td>" +
            "<td class=\"text-end fw-bold\">$" + Number(orden.total || 0).toFixed(2) + "</td>" +
            "<td><span class=\"badge badge-light-primary\">" + esc(orden.estatus || "") + "</span></td>" +
            "</tr>";
    }

    function cargarDiferenciasSolicitud() {
        var id = Number(document.getElementById("solicitud_id").value || 0);
        if (!id) {
            return Promise.resolve(null);
        }
        return fetch("/compra/orden_diferencias_solicitud_erp?id_solicitud=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) {
                    throw new Error(r.mensaje || "No se pudieron consultar diferencias");
                }
                var data = r.depurar || {};
                var resumen = data.resumen || {};
                var faltantes = data.faltantes || [];
                var adicionales = data.adicionales || [];
                var cambios = data.cambios || [];
                document.getElementById("solicitud_dif_resumen_faltantes").textContent = String(resumen.faltantes || 0);
                document.getElementById("solicitud_dif_resumen_adicionales").textContent = String(resumen.adicionales || 0);
                document.getElementById("solicitud_dif_resumen_cambios").textContent = String(resumen.cambios || 0);
                document.getElementById("solicitud_dif_faltantes").innerHTML = renderizarDiferenciasFila(faltantes, renderFilaDiferenciaSimple) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin faltantes</td></tr>";
                document.getElementById("solicitud_dif_adicionales").innerHTML = renderizarDiferenciasFila(adicionales, renderFilaDiferenciaSimple) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin adicionales</td></tr>";
                document.getElementById("solicitud_dif_cambios").innerHTML = renderizarDiferenciasFila(cambios, renderFilaDiferenciaCambio) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin cambios</td></tr>";
                return r;
            });
    }

    function abrirDiferenciasSolicitud() {
        var id = Number(document.getElementById("solicitud_id").value || 0);
        if (!id) {
            return;
        }
        cargarDiferenciasSolicitud().then(function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById("solicitud_diferencias_modal")).show();
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo consultar diferencias", icon: "error", confirmButtonText: "Aceptar"});
            var boton = document.getElementById("solicitud_ver_diferencias");
            if (boton) { boton.classList.add("d-none"); }
        });
    }

    function deshabilitar() {
        document.querySelectorAll(".solicitud-edicion").forEach(function (x) {
            x.classList.add("d-none");
        });
        ["solicitud_proveedor", "solicitud_almacen", "solicitud_prioridad", "solicitud_fecha_requerida", "solicitud_observaciones"].forEach(function (id) {
            document.getElementById(id).disabled = true;
        });
        ["solicitud_sku_nuevo", "solicitud_nombre_nuevo", "solicitud_cantidad_nueva", "solicitud_costo_nuevo", "solicitud_agregar_nuevo"].forEach(function (id) {
            document.getElementById(id).disabled = true;
        });
    }

    function buscar() {
        var proveedor = document.getElementById("solicitud_proveedor").value;
        var q = document.getElementById("solicitud_buscar_sku").value.trim();
        var box = document.getElementById("solicitud_resultados");
        if (!proveedor || q.length < 2) {
            box.classList.add("d-none");
            return;
        }
        fetch("/compra/solicitudes_buscar_skus_erp?" + new URLSearchParams({id_proveedor: proveedor, q: q}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                box.innerHTML = (r.depurar || []).map(function (x) {
                    return "<button type=\"button\" class=\"btn btn-flush text-start w-100 p-4 border-bottom\" data-sku='" +
                        encodeURIComponent(JSON.stringify(x)) + "'><span class=\"fw-bold\">" + esc(x.sku) +
                        "</span> " + esc(x.nombre) + "<span class=\"float-end\">$" + Number(x.costo_ultimo || 0).toFixed(2) + "</span>" +
                        evidenciaCostoHtml(x) + advertenciasOperativasHtml(x) + "</button>";
                }).join("") || "<div class=\"p-5 text-muted\">Sin relacion activa proveedor-SKU. Revisa Proveedores/Catalogo o captura producto propuesto.</div>";
                box.classList.remove("d-none");
            });
    }

    function agregarDesdeCatalogo(data) {
        var item = {
            id_sku_erp: data.id_sku,
            sku: data.sku,
            nombre: data.nombre,
            unidad: data.unidad,
            sku_proveedor: data.sku_proveedor,
            cantidad: Math.max(1, Number(data.cantidad_minima || 1)),
            costo_estimado: Number(data.costo_ultimo || 0),
            observaciones: "",
            fuente_costo: data.fuente_costo || "",
            origen_costo: data.origen_costo || "",
            moneda_costo: data.moneda_costo || "",
            vigencia_desde: data.vigencia_desde || "",
            vigencia_hasta: data.vigencia_hasta || "",
            id_costo_proveedor_sku: data.id_costo_proveedor_sku || 0,
            advertencias_operativas: data.advertencias_operativas || [],
            es_nuevo: false
        };
        if (existeItem(item)) {
            return;
        }
        items.push(item);
        document.getElementById("solicitud_resultados").classList.add("d-none");
        document.getElementById("solicitud_buscar_sku").value = "";
        render();
    }

    function agregarSugerido() {
        var item = {
            id_sku_erp: 0,
            sku: document.getElementById("solicitud_sku_nuevo").value,
            nombre: document.getElementById("solicitud_nombre_nuevo").value,
            unidad: "",
            sku_proveedor: "",
            cantidad: Number(document.getElementById("solicitud_cantidad_nueva").value || 0),
            costo_estimado: Number(document.getElementById("solicitud_costo_nuevo").value || 0),
            observaciones: "",
            es_nuevo: true
        };
        if (!item.sku.trim() || !item.nombre.trim() || item.cantidad <= 0) {
            Swal.fire({text: "Para agregar producto sugerido, captura SKU, nombre y cantidad mayor a 0", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (existeItem(item)) {
            Swal.fire({text: "Este producto sugerido ya fue agregado", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        items.push(item);
        document.getElementById("solicitud_sku_nuevo").value = "";
        document.getElementById("solicitud_nombre_nuevo").value = "";
        document.getElementById("solicitud_cantidad_nueva").value = "1";
        document.getElementById("solicitud_costo_nuevo").value = "0";
        render();
    }

    function render() {
        var editable = puedeEditar;
        document.getElementById("solicitud_items").innerHTML = items.map(function (x, i) {
            var subtotal = Number(x.cantidad || 0) * Number(x.costo_estimado || 0);
            var tipo = tipoBadge(x);
            var costoAjustado = Number(x.costo_estimado || 0).toFixed(2);
            var pasoCantidad = normalizarCantidadPorUnidad(x.unidad);
            var advertencias = advertenciasOperativasHtml(x);
            var evidenciaCosto = evidenciaCostoHtml(x);
            return "<tr>" +
                "<td>" +
                "<div class=\"fw-bold\">" + esc(x.sku) + "</div>" +
                "<div class=\"text-muted fs-8\">" + tipo + esc(x.sku_proveedor || "") + "</div>" + evidenciaCosto + advertencias +
                "</td>" +
                "<td>" +
                esc(x.nombre) +
                "<div class=\"text-muted fs-8\">" + esc(x.unidad || "") + "</div>" +
                "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" type=\"number\" min=\"" + pasoCantidad + "\" step=\"" + pasoCantidad + "\" data-item-cantidad=\"" + i +
                "\" value=\"" + x.cantidad + "\" onwheel=\"this.blur()\"" + (editable ? "" : " disabled") + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" type=\"number\" min=\"0\" step=\"0.01\" data-item-costo=\"" + i +
                "\" value=\"" + costoAjustado + "\" onwheel=\"this.blur()\"" + (editable ? "" : " disabled") + "></td>" +
                "<td class=\"text-end fw-bold\">$" + subtotal.toFixed(2) + "</td>" +
                "<td><input class=\"form-control form-control-sm\" data-item-observaciones=\"" + i + "\" value=\"" + esc(x.observaciones) +
                "\"" + (editable ? "" : " disabled") + "></td>" +
                "<td class=\"solicitud-edicion text-end" + (editable ? "" : " d-none") + "\"><button class=\"btn btn-sm btn-icon btn-light-danger\" data-item-quitar=\"" + i +
                "\"><i class=\"bi bi-trash\"></i></button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Agrega productos relacionados con el proveedor</td></tr>";

        var total = items.reduce(function (t, x) {
            return t + Number(x.cantidad || 0) * Number(x.costo_estimado || 0);
        }, 0);
        document.getElementById("solicitud_total").textContent = "$" + total.toFixed(2);
    }

    function validarAntesGuardar(estatus) {
        if (!document.getElementById("solicitud_proveedor").value) {
            throw new Error("Selecciona un proveedor");
        }
        if (estatus === "pendiente" && items.length === 0) {
            throw new Error("Agrega al menos un producto para enviar a aprobacion");
        }
        if (estatus === "pendiente" && !document.getElementById("solicitud_almacen").value) {
            throw new Error("Selecciona el almacen destino para enviar a aprobacion");
        }
        var invalidos = items.some(function (x) {
            return Number(x.cantidad || 0) <= 0 || (estatus === "pendiente" && Number(x.costo_estimado || 0) <= 0);
        });
        if (invalidos) {
            throw new Error("Revisa que todos los productos tengan cantidad mayor a 0" +
                (estatus === "pendiente" ? " y costo estimado mayor a 0" : ""));
        }
    }

    function guardar(estatus) {
        try {
            validarAntesGuardar(estatus);
        } catch (e) {
            Swal.fire({text: e.message, icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var data = {
            id_solicitud: document.getElementById("solicitud_id").value,
            id_proveedor: document.getElementById("solicitud_proveedor").value,
            id_almacen_destino: document.getElementById("solicitud_almacen").value,
            prioridad: document.getElementById("solicitud_prioridad").value,
            fecha_requerida: document.getElementById("solicitud_fecha_requerida").value,
            observaciones: document.getElementById("solicitud_observaciones").value,
            estatus: estatus,
            items: JSON.stringify(items)
        };
        post("/compra/solicitud_guardar_erp", data)
            .then(function (r) {
                if (r.error) {
                    throw new Error(r.mensaje);
                }
                Swal.fire({text: r.mensaje, icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                    window.location.href = "/compra/mostrar_solicitudes";
                });
            })
            .catch(function (e) {
                Swal.fire({text: e.message, icon: "error", confirmButtonText: "Aceptar"});
            });
    }

    function enviarCambioEstatus(estatus, motivo) {
        var payload = {id_solicitud: document.getElementById("solicitud_id").value, estatus: estatus};
        if (motivo) {
            payload.motivo = motivo;
        }
        post("/compra/solicitud_estatus_erp", payload)
            .then(function (r) {
                if (r.error) {
                    throw new Error(r.mensaje);
                }
                window.location.reload();
            })
            .catch(function (x) {
                Swal.fire({text: x.message, icon: "error"});
            });
    }

    function solicitarCambio(estatus) {
        if ((estatus === "rechazada" || estatus === "cancelada") && !permisos[estatus === "cancelada" ? "cancelar" : "aprobar"]) {
            Swal.fire({text: "No tienes permiso para esta accion", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (estatus === "rechazada" || estatus === "cancelada") {
            var accion = estatus === "cancelada" ? "cancelación" : "rechazo";
            Swal.fire({
                title: "Motivo de " + accion,
                input: "textarea",
                inputLabel: "Registra el motivo",
                inputPlaceholder: "Escribe el motivo para dejar trazabilidad",
                showCancelButton: true,
                confirmButtonText: "Guardar"
            }).then(function (resp) {
                if (!resp.isConfirmed) {
                    return;
                }
                var motivo = (resp.value || "").trim();
                if (!motivo) {
                    Swal.fire({text: "El motivo es obligatorio", icon: "warning", confirmButtonText: "Aceptar"});
                    return;
                }
                enviarCambioEstatus(estatus, motivo);
            });
            return;
        }
        if (estatus === "aprobada" && !permisos.aprobar) {
            Swal.fire({text: "No tienes permiso para aprobar", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (estatus === "cancelada" && !permisos.cancelar) {
            Swal.fire({text: "No tienes permiso para cancelar", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        enviarCambioEstatus(estatus);
    }

    function limpiarNuevaFila() {
        document.getElementById("solicitud_sku_nuevo").value = "";
        document.getElementById("solicitud_nombre_nuevo").value = "";
        document.getElementById("solicitud_cantidad_nueva").value = "1";
        document.getElementById("solicitud_costo_nuevo").value = "0";
    }

    document.addEventListener("DOMContentLoaded", function () {
        modo = document.getElementById("solicitud_modo").value;
        leerPermisos();
        cargarCatalogos().then(cargarSolicitud).then(function () {
            var proveedor = document.getElementById("solicitud_proveedor");
            document.getElementById("solicitud_buscar_sku").disabled = !puedeEditar || !proveedor.value;
        });

        document.getElementById("solicitud_proveedor").addEventListener("change", function () {
            items = [];
            render();
            document.getElementById("solicitud_buscar_sku").disabled = !this.value || !puedeEditar;
            limpiarNuevaFila();
        });

        document.getElementById("solicitud_buscar_sku").addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(buscar, 250);
        });

        var botonDiferencias = document.getElementById("solicitud_ver_diferencias");
        if (botonDiferencias) {
            botonDiferencias.addEventListener("click", abrirDiferenciasSolicitud);
        }

        document.getElementById("solicitud_resultados").addEventListener("click", function (e) {
            var b = e.target.closest("[data-sku]");
            if (b) {
                agregarDesdeCatalogo(JSON.parse(decodeURIComponent(b.getAttribute("data-sku"))));
            }
        });

        document.getElementById("solicitud_agregar_nuevo").addEventListener("click", agregarSugerido);

        document.getElementById("solicitud_items").addEventListener("change", function (e) {
            ["cantidad", "costo", "observaciones"].forEach(function (campo) {
                var a = e.target.getAttribute("data-item-" + campo);
                if (a == null) { return; }
                if (campo === "observaciones") {
                    items[a][campo] = e.target.value;
                    return;
                }
                items[a][campo === "costo" ? "costo_estimado" : campo] = Number(e.target.value);
            });
            render();
        });

        document.getElementById("solicitud_items").addEventListener("click", function (e) {
            var b = e.target.closest("[data-item-quitar]");
            if (b) {
                items.splice(Number(b.getAttribute("data-item-quitar")), 1);
                render();
            }
        });

        document.getElementById("solicitud_guardar_borrador").addEventListener("click", function () {
            guardar("borrador");
        });
        document.getElementById("solicitud_enviar").addEventListener("click", function () {
            guardar("pendiente");
        });
        document.getElementById("solicitud_aprobacion").addEventListener("click", function (e) {
            var b = e.target.closest("[data-solicitud-estatus]");
            if (b) {
                solicitarCambio(b.getAttribute("data-solicitud-estatus"));
            }
        });
        document.getElementById("solicitud_generar_orden_btn").addEventListener("click", function () {
            post("/compra/orden_generar_desde_solicitud_erp", {
                id_solicitud: document.getElementById("solicitud_id").value
            }).then(function (r) {
                if (r.error) {
                    throw new Error(r.mensaje);
                }
                window.location.href = "/compra/editar_orden_compra/" + r.depurar.id_orden_compra;
            }).catch(function (x) {
                Swal.fire({text: x.message, icon: "error"});
            });
        });

        render();
    });
})();
