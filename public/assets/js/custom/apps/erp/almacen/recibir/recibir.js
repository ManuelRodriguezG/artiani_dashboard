"use strict";

var KTAlmacenRecibir = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            "info": false,
            "order": [],
            "paging": false,
            "columnDefs": [
                {orderable: false, targets: [5, 6, 7, 8, 9, 10, 11]}
            ]
        });
    };

    var handleSearchDatatable = function () {
        const filterSearch = document.querySelector('[data-kt-almacen-recibir-filter="search"]');
        filterSearch.addEventListener("keyup", function (e) {
            datatable.search(e.target.value).draw();
        });
    };

    return {
        init: function () {
            table = document.querySelector("#kt_almacen_recibir_table");
            if (!table) {
                return;
            }

            cargar_recepcion_almacen();
            initDatatable();
            handleSearchDatatable();
            handleGuardarRecepcion();
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTAlmacenRecibir.init();
});

function cargar_recepcion_almacen() {
    const idRecepcion = parseInt($("#id_recepcion_almacen").val()) || 0;
    if (idRecepcion <= 0) {
        return;
    }

    const respuesta = consultar_recepcion_almacen(idRecepcion);
    if (respuesta.error == true) {
        return;
    }

    ui_recepcion_encabezado(respuesta.depurar.recepcion);
    ui_recepcion_detalle(respuesta.depurar.detalle);
}

function ui_recepcion_encabezado(recepcion) {
    const estatus = (recepcion.estatus || "pendiente").toLowerCase();
    $("#recepcion_folio").text(recepcion.folio || "Recepcion");
    $("#recepcion_estatus")
            .removeClass("badge-light-primary badge-light-warning badge-light-success badge-light-danger badge-light-secondary")
            .addClass(clase_badge_estatus_recepcion(estatus))
            .text(estatus);
    $("#id_recepcion_almacen").attr("data-estatus", estatus);
    $("#recepcion_orden_compra")
            .attr("href", "/compra/ver_orden_compra/" + recepcion.id_orden_compra)
            .text(recepcion.folio_orden_compra || recepcion.id_orden_compra || "-");
    $("#recepcion_proveedor").text(recepcion.proveedor || "-");
    $("#recepcion_almacen").text(recepcion.almacen || "-");
    $("#recepcion_fecha_alerta").text(recepcion.fecha_alerta || "-");
    if (estatus === "recibida" || estatus === "cancelada") {
        $("#btn_guardar_recepcion").text("Recepcion cerrada");
    } else {
        $("#btn_guardar_recepcion").text("Guardar recepcion");
    }
}

function clase_badge_estatus_recepcion(estatus) {
    if (estatus === "recibida") {
        return "badge-light-success";
    }
    if (estatus === "parcial") {
        return "badge-light-warning";
    }
    if (estatus === "cancelada") {
        return "badge-light-danger";
    }
    return "badge-light-primary";
}

function ui_recepcion_detalle(detalle) {
    let codigo = "";
    Object.entries(detalle).forEach(function (row) {
        codigo += item_recepcion_detalle(row[1]);
    });
    $("#body-recepcion-detalle").html(codigo);
    recalcular_todas_las_partidas_recepcion();
    const estatus = ($("#id_recepcion_almacen").attr("data-estatus") || "pendiente").toLowerCase();
    $("#btn_guardar_recepcion").prop("disabled", Object.entries(detalle).length <= 0 || estatus === "recibida" || estatus === "cancelada");
}

function item_recepcion_detalle(producto) {
    const id = producto.id_recepcion_detalle;
    const pendiente = parseFloat(producto.cantidad_pendiente || 0);
    const ordenada = parseFloat(producto.cantidad_ordenada || 0);
    const recibida = parseFloat(producto.cantidad_recibida || 0);
    const ordenadaBase = parseFloat(producto.cantidad_ordenada_base || 0);
    const recibidaBase = parseFloat(producto.cantidad_recibida_base || 0);
    const pendienteBase = parseFloat(producto.cantidad_pendiente_base || 0);
    const factorConversion = parseFloat(producto.factor_conversion || 0);
    const unidadCompra = producto.unidad_compra || producto.unidad || "";
    const unidadBase = producto.unidad_base || producto.unidad || "";
    const alertaBloqueante = parseInt(producto.alerta_bloqueante || 0) === 1;
    const skuVinculado = parseInt(producto.id_sku_erp || 0) > 0;
    const deshabilitado = pendiente <= 0 || !skuVinculado || alertaBloqueante ? "disabled" : "";
    const requiereLote = parseInt(producto.requiere_lote || 0) === 1;
    const requiereCaducidad = parseInt(producto.requiere_caducidad || 0) === 1;
    const requiereCodigoUnico = parseInt(producto.requiere_codigo_unico || 0) === 1;
    const generarEtiquetaIndividual = parseInt(producto.generar_etiqueta_individual || 0) === 1;
    const generarCodigoDefault = requiereCodigoUnico || generarEtiquetaIndividual;
    const requiereCantidadVariable = parseInt(producto.requiere_cantidad_variable_recepcion || 0) === 1;
    const requiereUnidadesFisicas = parseInt(producto.requiere_unidades_fisicas_recepcion || 0) === 1;
    const prefijoCodigoUnico = producto.prefijo_codigo_unico || "";
    const diasAlerta = parseInt(producto.dias_alerta_caducidad || 90) || 90;
    const diasMinimos = parseInt(producto.dias_minimos_recepcion || 30) || 30;
    const control = etiquetas_control_producto(producto);
    const unidadCompraCaptura = "unidades";
    const cantidadRealDefault = requiereCantidadVariable ? (pendienteBase > 0 ? pendienteBase : Math.max(0, pendiente * Math.max(factorConversion, 1))) : 0;

    return `
        <tr data-id-recepcion-detalle="${id}" data-id-producto="${producto.id_producto}" data-id-sku-erp="${producto.id_sku_erp || 0}" data-cantidad-pendiente-original="${pendiente}" data-factor-conversion="${factorConversion || 0}" data-unidad-compra="${unidadCompra}" data-unidad-base="${unidadBase}" data-alerta-bloqueante="${alertaBloqueante ? 1 : 0}" data-requiere-lote="${requiereLote ? 1 : 0}" data-requiere-caducidad="${requiereCaducidad ? 1 : 0}" data-requiere-codigo-unico="${requiereCodigoUnico ? 1 : 0}" data-generar-etiqueta-individual="${generarEtiquetaIndividual ? 1 : 0}" data-requiere-cantidad-variable-recepcion="${requiereCantidadVariable ? 1 : 0}" data-requiere-unidades-fisicas-recepcion="${requiereUnidadesFisicas ? 1 : 0}" data-prefijo-codigo-unico="${prefijoCodigoUnico}" data-dias-alerta-caducidad="${diasAlerta}" data-dias-minimos-recepcion="${diasMinimos}" data-fila-principal="1">
            <td>
                <span class="fw-bold">${producto.sku || ""}</span>
                ${skuVinculado ? "" : '<div class="text-danger fs-8">Sin SKU maestro ERP</div>'}
            </td>
            <td>
                <div class="fw-bold text-gray-800">${producto.nombre_producto || ""}</div>
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${formato_cantidad_recepcion(ordenada)} ${unidadCompraCaptura}</span>
                ${factorConversion > 0 ? `<div class="fs-8 text-muted">${formato_cantidad_recepcion(ordenadaBase)} ${unidadBase}</div>` : ""}
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${formato_cantidad_recepcion(recibida)} ${unidadCompraCaptura}</span>
                ${factorConversion > 0 ? `<div class="fs-8 text-muted">${formato_cantidad_recepcion(recibidaBase)} ${unidadBase}</div>` : ""}
            </td>
            <td class="text-end pe-0">
                <div class="d-flex flex-column align-items-end">
                    <span class="fw-bold span-pendiente-restante">${formato_cantidad_recepcion(pendiente)} ${unidadCompraCaptura}</span>
                    ${factorConversion > 0 ? `<span class="fs-8 text-muted">${formato_cantidad_recepcion(pendienteBase)} ${unidadBase}</span>` : ""}
                    <span class="badge badge-light-primary badge-estado-recepcion mt-1">pendiente</span>
                </div>
            </td>
            <td>
                ${control}
                <div class="fs-8 text-muted mt-1">Alerta: ${diasAlerta} dias</div>
                <div class="fs-8 text-primary mt-2 etiquetas-unidad-preview">${texto_etiquetas_individuales(generarCodigoDefault, pendiente)}</div>
            </td>
            <td>
                <input type="text" class="form-control form-control-solid form-control-sm input-lote" oninput="validar_lote_partida(this); actualizar_codigo_lote_partida(this);" ${deshabilitado} />
                <div class="fs-8 text-danger mt-1 aviso-lote"></div>
            </td>
            <td>
                <input type="date" class="form-control form-control-solid form-control-sm input-caducidad" onchange="validar_caducidad_partida(this); actualizar_codigo_lote_partida(this);" ${deshabilitado} />
                <div class="fs-8 mt-1 aviso-caducidad"></div>
            </td>
            <td>
                <input type="text" class="form-control form-control-solid form-control-sm input-ubicacion" ${deshabilitado} />
            </td>
            <td>
                <div class="input-group input-group-sm w-md-175px ms-auto" data-kt-dialer="true" data-kt-dialer-step="1">
                    <button class="btn btn-icon btn-outline btn-active-color-primary" type="button" data-kt-dialer-control="decrease" onclick="cambio_cantidad_recibir(this);" ${deshabilitado}>
                        <i class="bi bi-dash fs-1"></i>
                    </button>
                    <input type="number" step="0.0001" min="0" value="${pendiente > 0 ? pendiente.toFixed(4) : "0.0000"}" class="form-control form-control-solid text-end input-cantidad-recibir" data-kt-dialer-control="input" oninput="recalcular_partida_recepcion(this);" onblur="normalizar_cantidad_recibir(this);" ${deshabilitado} />
                    <button class="btn btn-icon btn-outline btn-active-color-primary" type="button" data-kt-dialer-control="increase" onclick="cambio_cantidad_recibir(this);" ${deshabilitado}>
                        <i class="bi bi-plus fs-1"></i>
                    </button>
                </div>
                <div class="fs-8 text-muted text-end mt-1 cantidad-base-preview"></div>
                ${requiereCantidadVariable ? `
                    <div class="mt-2">
                        <label class="fs-8 text-muted mb-1">Cantidad real ${unidadBase}</label>
                        <input type="number" step="0.000001" min="0" value="${cantidadRealDefault > 0 ? cantidadRealDefault.toFixed(6) : ""}" class="form-control form-control-solid form-control-sm text-end input-cantidad-base-real" oninput="recalcular_partida_recepcion(this);" onblur="normalizar_cantidad_base_real(this);" ${deshabilitado} />
                    </div>
                ` : ""}
            </td>
            <td>
                <div class="fw-bold text-gray-800 codigo-lote-preview">${generar_codigo_lote_local_desde_datos(producto.id_producto, id, "", "")}</div>
                <div class="fs-8 text-muted">Existencia/lote</div>
                <div class="fs-8 text-muted">Regla desde Catalogo</div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light-primary" onclick="agregar_lote_partida(this);" ${deshabilitado}>Agregar lote</button>
            </td>
        </tr>
    `;
}

function etiquetas_control_producto(producto) {
    let etiquetas = "";
    if (parseInt(producto.requiere_cantidad_variable_recepcion || 0) === 1) {
        etiquetas += `<span class="badge badge-light-success me-1">Cantidad real</span>`;
    }
    if (parseInt(producto.requiere_lote || 0) === 1) {
        etiquetas += `<span class="badge badge-light-info me-1">Lote</span>`;
    }
    if (parseInt(producto.requiere_caducidad || 0) === 1) {
        etiquetas += `<span class="badge badge-light-warning me-1">Caducidad</span>`;
    }
    if (parseInt(producto.requiere_codigo_unico || 0) === 1) {
        etiquetas += `<span class="badge badge-light-primary me-1">Serie fabricante</span>`;
    }
    if (parseInt(producto.generar_etiqueta_individual || 0) === 1) {
        etiquetas += `<span class="badge badge-light-primary me-1">Etiqueta trazabilidad</span>`;
    }
    if (!etiquetas) {
        etiquetas = `<span class="badge badge-light-secondary">Sin control</span>`;
    }
    return etiquetas;
}

function formato_cantidad_recepcion(valor) {
    const numero = parseFloat(valor || 0);
    return numero.toFixed(4);
}

function texto_conversion_recepcion(factor, unidadCompra, unidadBase) {
    if (!factor || factor <= 0) {
        return "Unidad/factor pendiente";
    }
    if (!unidadCompra && !unidadBase) {
        return "";
    }
    return `Contenido por unidad de compra: ${formato_cantidad_recepcion(factor)} ${unidadBase || "unidad base"}`;
}

function alertas_recepcion_html(alertas) {
    if (!Array.isArray(alertas) || alertas.length <= 0) {
        return "";
    }

    return alertas.map(function (alerta) {
        const severidad = (alerta.severidad || "media").toLowerCase();
        const clase = severidad === "alta" ? "text-danger" : "text-warning";
        return `<div class="fs-8 ${clase} mt-1">${alerta.mensaje || "Validacion pendiente"}</div>`;
    }).join("");
}

function cambio_cantidad_recibir(btn) {
    const row = $(btn).closest("tr");
    const input = row.find(".input-cantidad-recibir");
    const tipo = $(btn).attr("data-kt-dialer-control");
    let cantidad = parseFloat(input.val()) || 0;

    if (tipo === "increase") {
        cantidad += 1;
    } else if (tipo === "decrease") {
        cantidad -= 1;
    }

    if (cantidad < 0) {
        cantidad = 0;
    }

    input.val(cantidad.toFixed(4));
    recalcular_partida_recepcion(input[0]);
    actualizar_codigo_lote_partida(input[0]);
}

function normalizar_cantidad_recibir(input) {
    let cantidad = parseFloat($(input).val()) || 0;
    if (cantidad < 0) {
        cantidad = 0;
    }
    $(input).val(cantidad.toFixed(4));
    recalcular_partida_recepcion(input);
    actualizar_codigo_lote_partida(input);
}

/**
 * IA: Codex GPT-5 | Fecha: 2026-06-27
 * Proposito: normaliza la cantidad real capturada en unidad base para SKUs de recepcion variable.
 * Impacto: UI de Almacen/Recepciones; evita enviar cantidad base vacia o negativa al backend.
 */
function normalizar_cantidad_base_real(input) {
    let cantidad = parseFloat($(input).val()) || 0;
    if (cantidad < 0) {
        cantidad = 0;
    }
    $(input).val(cantidad > 0 ? cantidad.toFixed(6) : "");
    recalcular_partida_recepcion(input);
}

function recalcular_partida_recepcion(input) {
    const row = $(input).closest("tr");
    recalcular_grupo_recepcion(row.attr("data-id-recepcion-detalle"));
}

function recalcular_grupo_recepcion(idRecepcionDetalle) {
    const rows = $(`tr[data-id-recepcion-detalle="${idRecepcionDetalle}"]`);
    const rowPrincipal = rows.filter('[data-fila-principal="1"]').first();
    const pendienteOriginal = parseFloat(rowPrincipal.attr("data-cantidad-pendiente-original")) || 0;
    let cantidadRecibir = 0;

    rows.find(".input-cantidad-recibir").each(function () {
        cantidadRecibir += parseFloat($(this).val()) || 0;
    });

    const restante = pendienteOriginal - cantidadRecibir;
    rows.each(function () {
        pintar_estado_pendiente($(this), restante, cantidadRecibir, pendienteOriginal);
        actualizar_resumen_etiquetas_individuales($(this));
        actualizar_conversion_base_recepcion($(this));
    });
}

function actualizar_conversion_base_recepcion(row) {
    const requiereVariable = parseInt(row.attr("data-requiere-cantidad-variable-recepcion") || 0) === 1;
    const factor = parseFloat(row.attr("data-factor-conversion") || 0);
    const unidadBase = row.attr("data-unidad-base") || "";
    const cantidad = parseFloat(row.find(".input-cantidad-recibir").val()) || 0;
    const cantidadReal = parseFloat(row.find(".input-cantidad-base-real").val()) || 0;
    const preview = row.find(".cantidad-base-preview");

    if (requiereVariable) {
        preview.text(cantidadReal > 0 ? `Entrada inventario: ${formato_cantidad_recepcion(cantidadReal)} ${unidadBase}` : "Captura cantidad real");
        return;
    }

    if (!factor || factor <= 0) {
        preview.text("Base pendiente");
        return;
    }

    preview.text(`${formato_cantidad_recepcion(cantidad * factor)} ${unidadBase}`);
}

function pintar_estado_pendiente(row, restante, cantidadRecibir, pendienteOriginal) {
    const spanPendiente = row.find(".span-pendiente-restante");
    const badge = row.find(".badge-estado-recepcion");

    spanPendiente.text(`${restante.toFixed(4)} unidades`);
    badge.removeClass("badge-light-primary badge-light-danger badge-light-info badge-light-warning badge-light-success");
    spanPendiente.removeClass("text-primary text-danger text-info text-warning text-success");

    if (pendienteOriginal <= 0) {
        badge.addClass("badge-light-success").text("recibida");
        spanPendiente.addClass("text-success");
        return;
    }

    if (cantidadRecibir <= 0 && pendienteOriginal > 0) {
        badge.addClass("badge-light-danger").text("sin captura");
        spanPendiente.addClass("text-danger");
        return;
    }

    if (restante > 0) {
        badge.addClass("badge-light-danger").text("parcial por guardar");
        spanPendiente.addClass("text-danger");
        return;
    }

    if (restante < 0) {
        badge.addClass("badge-light-warning").text("excedente");
        spanPendiente.addClass("text-warning");
        return;
    }

    badge.addClass("badge-light-info").text("listo por guardar");
    spanPendiente.addClass("text-info");
}

function recalcular_todas_las_partidas_recepcion() {
    const ids = {};
    $("tr[data-id-recepcion-detalle]").each(function () {
        ids[$(this).attr("data-id-recepcion-detalle")] = true;
    });
    Object.keys(ids).forEach(function (id) {
        recalcular_grupo_recepcion(id);
    });
}

function agregar_lote_partida(btn) {
    const row = $(btn).closest("tr");
    const nuevo = row.clone(false);
    const id = row.attr("data-id-recepcion-detalle");

    nuevo.attr("data-fila-principal", "0");
    nuevo.find("td").eq(0).html('<span class="text-muted fs-7">Lote adicional</span>');
    nuevo.find("td").eq(1).html(row.find("td").eq(1).html());
    nuevo.find("td").eq(2).html("");
    nuevo.find("td").eq(3).html("");
    nuevo.find(".input-lote").val("");
    nuevo.find(".input-caducidad").val("");
    nuevo.find(".input-ubicacion").val(row.find(".input-ubicacion").val());
    nuevo.find(".input-cantidad-recibir").val("0.0000");
    nuevo.find(".input-cantidad-base-real").val("");
    nuevo.find(".codigo-lote-preview").text(generar_codigo_lote_local(nuevo));
    actualizar_resumen_etiquetas_individuales(nuevo);
    nuevo.find(".aviso-lote").text("");
    nuevo.find(".aviso-caducidad").text("").removeClass("text-danger text-warning text-info");
    nuevo.find("td").last().html(`<button type="button" class="btn btn-sm btn-light-danger" onclick="eliminar_lote_partida(this);">Quitar</button>`);

    const ultFila = $(`tr[data-id-recepcion-detalle="${id}"]`).last();
    ultFila.after(nuevo);
    recalcular_grupo_recepcion(id);
}

function eliminar_lote_partida(btn) {
    const row = $(btn).closest("tr");
    const id = row.attr("data-id-recepcion-detalle");
    row.remove();
    recalcular_grupo_recepcion(id);
}

function validar_lote_partida(input) {
    const row = $(input).closest("tr");
    const requiereLote = parseInt(row.attr("data-requiere-lote") || 0) === 1;
    const aviso = row.find(".aviso-lote");
    aviso.text("");
    if (requiereLote && !$(input).val().trim()) {
        aviso.text("Lote requerido");
    }
}

function validar_caducidad_partida(input) {
    const row = $(input).closest("tr");
    const requiereCaducidad = parseInt(row.attr("data-requiere-caducidad") || 0) === 1;
    const diasAlerta = parseInt(row.attr("data-dias-alerta-caducidad") || 90) || 90;
    const diasMinimos = parseInt(row.attr("data-dias-minimos-recepcion") || 30) || 30;
    const aviso = row.find(".aviso-caducidad");
    const valor = $(input).val();

    aviso.text("").removeClass("text-danger text-warning text-info");
    if (!valor) {
        if (requiereCaducidad) {
            aviso.addClass("text-danger").text("Caducidad requerida");
        }
        return;
    }

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fecha = new Date(valor + "T00:00:00");
    const dias = Math.ceil((fecha - hoy) / (1000 * 60 * 60 * 24));

    if (dias < 0) {
        aviso.addClass("text-danger").text("Producto caducado");
        return;
    }
    if (dias <= diasMinimos) {
        aviso.addClass("text-danger").text("Muy proximo a vencer");
        return;
    }
    if (dias <= diasAlerta) {
        aviso.addClass("text-warning").text("Proximo a vencer");
        return;
    }
    aviso.addClass("text-info").text("Vigente");
}

function generar_codigo_lote_local(row) {
    const idProducto = row.attr("data-id-producto") || "0";
    const idDetalle = row.attr("data-id-recepcion-detalle") || "0";
    const lote = (row.find(".input-lote").val() || "SL").replace(/[^a-zA-Z0-9]/g, "").toUpperCase();
    const caducidad = (row.find(".input-caducidad").val() || "SC").replace(/-/g, "");
    return generar_codigo_lote_local_desde_datos(idProducto, idDetalle, lote, caducidad);
}

function generar_codigo_lote_local_desde_datos(idProducto, idDetalle, lote, caducidad) {
    const loteClave = lote || "SL";
    const caducidadClave = caducidad || "SC";
    return `INV-${idProducto}-${idDetalle}-${loteClave}-${caducidadClave}`;
}

function actualizar_codigo_lote_partida(input) {
    const row = $(input).closest("tr");
    row.find(".codigo-lote-preview").text(generar_codigo_lote_local(row));
    actualizar_resumen_etiquetas_individuales(row);
}

function actualizar_resumen_etiquetas_individuales(row) {
    const requiereCodigoUnico = parseInt(row.attr("data-requiere-codigo-unico") || 0) === 1;
    const generarEtiquetaIndividual = parseInt(row.attr("data-generar-etiqueta-individual") || 0) === 1;
    const cantidad = parseFloat(row.find(".input-cantidad-recibir").val()) || 0;
    row.find(".etiquetas-unidad-preview").text(texto_etiquetas_individuales(requiereCodigoUnico || generarEtiquetaIndividual, cantidad));
}

function texto_etiquetas_individuales(requiereCodigoUnico, cantidad) {
    if (!requiereCodigoUnico) {
        return "Sin etiqueta individual";
    }

    const piezas = Math.max(0, Math.floor(cantidad));
    if (piezas <= 0) {
        return "Sin piezas para etiquetar";
    }

    return `Generara ${piezas} etiqueta(s) al guardar`;
}

function handleGuardarRecepcion() {
    $("#btn_guardar_recepcion").on("click", function () {
        const btn = this;
        const validacion = recolectar_partidas_recepcion();

        if (validacion.error) {
            mostrar_alerta_recepcion("warning", validacion.mensaje);
            return;
        }

        btn.disabled = true;
        $(btn).attr("data-kt-indicator", "on");

        const respuesta = guardar_recepcion_almacen($("#id_recepcion_almacen").val(), validacion.partidas);

        $(btn).removeAttr("data-kt-indicator");
        btn.disabled = false;

        if (respuesta.error) {
            mostrar_alerta_recepcion("error", respuesta.mensaje || "No se pudo guardar la recepcion");
            return;
        }

        mostrar_resultado_guardado_recepcion(respuesta);
    });
}

function mostrar_resultado_guardado_recepcion(respuesta) {
    const unidades = parseInt((respuesta.depurar && respuesta.depurar.unidades) || 0);
    const folio = ($("#recepcion_folio").text() || "").trim();
    if (unidades > 0 && typeof Swal !== "undefined") {
        Swal.fire({
            text: (respuesta.mensaje || "Recepcion guardada correctamente") + ". Se generaron " + unidades + " etiqueta(s) de trazabilidad.",
            icon: "success",
            buttonsStyling: false,
            showCancelButton: true,
            confirmButtonText: "Ir a etiquetado",
            cancelButtonText: "Quedar aqui",
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = "/almacen/etiquetado?q=" + encodeURIComponent(folio) + "&estado_etiqueta=pendiente_impresion";
                return;
            }
            window.location.reload();
        });
        return;
    }

    mostrar_alerta_recepcion("success", respuesta.mensaje || "Recepcion guardada correctamente", function () {
        window.location.reload();
    });
}

function recolectar_partidas_recepcion() {
    const partidas = [];
    const cantidadesPorDetalle = {};
    const pendientesPorDetalle = {};
    let error = "";

    $("tr[data-id-recepcion-detalle]").each(function () {
        if (error) {
            return;
        }

        const row = $(this);
        const cantidad = parseFloat(row.find(".input-cantidad-recibir").val()) || 0;
        if (cantidad <= 0) {
            return;
        }

        const idDetalle = row.attr("data-id-recepcion-detalle");
        if (parseInt(row.attr("data-id-sku-erp") || 0) <= 0) {
            error = "Hay productos sin vincular a un SKU maestro ERP";
            return;
        }
        if (parseInt(row.attr("data-alerta-bloqueante") || 0) === 1) {
            error = "Hay partidas con alertas bloqueantes de unidad, factor, SKU o conversion. Resuelvelas antes de recibir.";
            return;
        }
        cantidadesPorDetalle[idDetalle] = (cantidadesPorDetalle[idDetalle] || 0) + cantidad;
        pendientesPorDetalle[idDetalle] = parseFloat(row.attr("data-cantidad-pendiente-original")) || 0;
        if (cantidadesPorDetalle[idDetalle] > pendientesPorDetalle[idDetalle] + 0.00001) {
            error = "La suma de lotes no puede superar la cantidad pendiente";
            return;
        }

        const requiereLote = parseInt(row.attr("data-requiere-lote") || 0) === 1;
        const requiereCaducidad = parseInt(row.attr("data-requiere-caducidad") || 0) === 1;
        const requiereCodigoUnico = parseInt(row.attr("data-requiere-codigo-unico") || 0) === 1 || parseInt(row.attr("data-generar-etiqueta-individual") || 0) === 1;
        const requiereCantidadVariable = parseInt(row.attr("data-requiere-cantidad-variable-recepcion") || 0) === 1;
        const requiereUnidadesFisicas = parseInt(row.attr("data-requiere-unidades-fisicas-recepcion") || 0) === 1;
        const cantidadBaseReal = parseFloat(row.find(".input-cantidad-base-real").val()) || 0;
        const lote = (row.find(".input-lote").val() || "").trim();
        const fechaCaducidad = (row.find(".input-caducidad").val() || "").trim();
        const ubicacion = (row.find(".input-ubicacion").val() || "").trim();

        if (requiereLote && !lote) {
            error = "Hay productos que requieren lote antes de guardar";
            return;
        }

        if (requiereCaducidad && !fechaCaducidad) {
            error = "Hay productos que requieren caducidad antes de guardar";
            return;
        }

        if (requiereCodigoUnico && Math.floor(cantidad) !== cantidad) {
            error = "Los productos con codigo unico deben recibirse en piezas enteras";
            return;
        }

        if (requiereUnidadesFisicas && Math.floor(cantidad) !== cantidad) {
            error = "Los productos con unidades fisicas deben capturarse en enteros";
            return;
        }

        if (requiereCantidadVariable && cantidadBaseReal <= 0) {
            error = "Captura la cantidad real recibida en unidad base";
            return;
        }

        partidas.push({
            id_recepcion_detalle: idDetalle,
            id_producto: row.attr("data-id-producto"),
            lote: lote,
            fecha_caducidad: fechaCaducidad,
            ubicacion: ubicacion,
            cantidad: cantidad,
            cantidad_base_real: requiereCantidadVariable ? cantidadBaseReal : "",
            generar_codigo_unico: requiereCodigoUnico ? 1 : 0,
            observaciones: ""
        });
    });

    if (error) {
        return {error: true, mensaje: error, partidas: []};
    }

    if (partidas.length <= 0) {
        return {error: true, mensaje: "Captura al menos una cantidad a recibir", partidas: []};
    }

    return {error: false, mensaje: "", partidas: partidas};
}

function mostrar_alerta_recepcion(tipo, mensaje, callback) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            text: mensaje,
            icon: tipo,
            buttonsStyling: false,
            confirmButtonText: "Aceptar",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        }).then(function () {
            if (typeof callback === "function") {
                callback();
            }
        });
        return;
    }

    if (typeof toastr !== "undefined") {
        if (tipo === "success") {
            toastr.success(mensaje);
        } else if (tipo === "warning") {
            toastr.warning(mensaje);
        } else {
            toastr.error(mensaje);
        }
    }

    if (typeof callback === "function") {
        callback();
    }
}
