"use strict";
(function () {
    var presentaciones = [];
    var preparaciones = [];
    var bases = [];
    var existenciasOrigen = [];
    var existenciaOrigenSeleccionada = "";
    var unidadOrigenSeleccionada = "";

    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function money(value) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 4, maximumFractionDigits: 6});
    }
    function refrescarSelectBuscable(id, placeholder) {
        var jq = window.jQuery;
        var element = document.getElementById(id);
        if (!jq || !jq.fn || !jq.fn.select2 || !element) { return; }
        var select = jq(element);
        if (select.hasClass("select2-hidden-accessible")) {
            select.select2("destroy");
        }
        select.select2({
            placeholder: placeholder || "",
            allowClear: true,
            width: "100%"
        });
        if (id === "alm_prep_sku_base") {
            select.off("change.almPrepSkuBase").on("change.almPrepSkuBase", alCambiarSkuBase);
        }
    }
    function sincronizarSelectBuscable(id) {
        var jq = window.jQuery;
        if (!jq || !jq.fn || !jq.fn.select2) { return; }
        jq("#" + id).trigger("change.select2");
    }
    function cargarAlmacenes() {
        return request("/almacen/consultar_almacenes?permite_preparacion=1").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            $("alm_prep_almacen").innerHTML = (response.depurar || []).map(function (item) {
                return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("") || "<option value=\"\">Sin almacenes para preparacion</option>";
        });
    }
    function cargarPresentaciones() {
        var params = new URLSearchParams({
            id_almacen: $("alm_prep_almacen").value || ""
        });
        return request("/almacen/preparacion_presentaciones_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            presentaciones = response.depurar || [];
            bases = basesDesdePresentaciones();
            renderBases();
            renderPresentacionesBase();
            renderResumen();
            cargarExistenciasBase();
        });
    }
    function basesDesdePresentaciones() {
        var vistos = {};
        return presentaciones.reduce(function (acumulado, item) {
            var id = String(item.id_sku_base || "");
            if (id && !vistos[id]) {
                vistos[id] = true;
                acumulado.push(item);
            }
            return acumulado;
        }, []);
    }
    function renderBases(selectedId) {
        $("alm_prep_sku_base").innerHTML = "<option value=\"\">Selecciona SKU origen</option>" + bases.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_sku_base) + "\">" + escapeHtml(item.sku_base) + " - " + escapeHtml(item.nombre_base) + "</option>";
        }).join("");
        if (!bases.length) {
            $("alm_prep_sku_base").innerHTML = "<option value=\"\">Sin SKUs origen preparables</option>";
        }
        if (selectedId) {
            $("alm_prep_sku_base").value = String(selectedId);
        }
        refrescarSelectBuscable("alm_prep_sku_base", "Selecciona SKU origen");
        sincronizarSelectBuscable("alm_prep_sku_base");
    }
    function renderPresentacionesBase(selectedRegla, selectedTransformacion) {
        var idBase = $("alm_prep_sku_base").value || "";
        var opciones = presentaciones
            .map(function (item, index) {
                item.__index = index;
                return item;
            })
            .filter(function (item) { return String(item.id_sku_base) === String(idBase); });
        $("alm_prep_presentacion").innerHTML = "<option value=\"\">Selecciona presentacion</option>" + opciones.map(function (item) {
            return "<option value=\"" + item.__index + "\">" + escapeHtml(item.sku_presentacion) + " - " + escapeHtml(item.nombre_presentacion) + "</option>";
        }).join("");
        if (!idBase) {
            $("alm_prep_presentacion").innerHTML = "<option value=\"\">Selecciona primero SKU origen</option>";
        } else if (!opciones.length) {
            $("alm_prep_presentacion").innerHTML = "<option value=\"\">Sin presentaciones para este SKU</option>";
        }
        if (selectedRegla || selectedTransformacion) {
            var seleccion = opciones.find(function (item) {
                return (selectedTransformacion && String(item.id_sku_transformacion) === String(selectedTransformacion)) ||
                    (selectedRegla && String(item.id_sku_presentacion_regla) === String(selectedRegla));
            });
            if (seleccion) {
                $("alm_prep_presentacion").value = String(seleccion.__index);
            }
        }
    }
    function alCambiarSkuBase() {
        renderPresentacionesBase();
        existenciaOrigenSeleccionada = "";
        renderResumen();
        cargarExistenciasBase();
    }
    function baseActual() {
        var idBase = $("alm_prep_sku_base").value || "";
        return bases.find(function (item) { return String(item.id_sku_base) === String(idBase); }) || null;
    }
    function presentacionActual() {
        var index = parseInt($("alm_prep_presentacion").value, 10);
        return presentaciones[index] || null;
    }
    function renderResumen() {
        var item = presentacionActual();
        var base = baseActual();
        if (!item) {
            if (base) {
                $("alm_prep_resumen").innerHTML =
                    "<div class=\"fw-bold\">" + escapeHtml(base.sku_base) + "</div>" +
                    "<div class=\"text-muted fs-7\">" + escapeHtml(base.nombre_base) + "</div>" +
                    "<div class=\"mt-3\"><span class=\"badge badge-light-primary\">Unidad base " + escapeHtml(base.unidad_base || "") + "</span></div>";
                return;
            }
            $("alm_prep_resumen").innerHTML = "Selecciona SKU origen.";
            return;
        }
        var unidades = Math.max(1, parseInt($("alm_prep_unidades").value || "1", 10));
        var consumo = unidades * Number(item.factor_salida_base || 0) * (1 + (Number(item.merma_porcentaje || 0) / 100));
        var ratio = item.unidades_resultado && Number(item.unidades_resultado) > 1
            ? "<span class=\"badge badge-light-dark\">" + money(item.cantidad_origen) + " origen -> " + escapeHtml(item.unidades_resultado) + " resultado</span>"
            : "";
        $("alm_prep_resumen").innerHTML =
            "<div class=\"fw-bold\">" + escapeHtml(item.sku_base) + " -> " + escapeHtml(item.sku_presentacion) + "</div>" +
            "<div class=\"text-muted fs-7\">" + escapeHtml(item.nombre_base) + "</div>" +
            "<div class=\"mt-3 d-flex flex-wrap gap-2\">" +
            "<span class=\"badge badge-light-primary\">Factor " + money(item.factor_salida_base) + " " + escapeHtml(item.unidad_base || "") + "</span>" +
            "<span class=\"badge badge-light-info\">Consumo " + money(consumo) + "</span>" +
            "<span class=\"badge badge-light-success\">Posibles " + escapeHtml(item.unidades_posibles || 0) + "</span>" +
            ratio +
            "</div>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: carga existencias origen y sus unidades fisicas trazables para preparacion.
     * Impacto: UI de Almacen/Preparacion; permite elegir la etiqueta/unidad exacta antes de guardar.
     * Contrato: si una existencia trae `unidades_fisicas`, el usuario debe seleccionar una unidad.
     */
    function cargarExistenciasBase() {
        var item = presentacionActual() || baseActual();
        if (!item || !item.id_sku_base) {
            $("alm_prep_existencias").innerHTML = "Sin SKU origen seleccionado.";
            return;
        }
        var params = new URLSearchParams({id_sku_base: item.id_sku_base, id_almacen: $("alm_prep_almacen").value || ""});
        request("/almacen/preparacion_existencias_base_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var rows = response.depurar || [];
            existenciasOrigen = rows;
            if (!rows.some(function (exi) { return String(exi.id_existencia_inventario) === String(existenciaOrigenSeleccionada); })) {
                existenciaOrigenSeleccionada = rows.length ? String(rows[0].id_existencia_inventario) : "";
                unidadOrigenSeleccionada = "";
            }
            if (!unidadOrigenValida(rows, unidadOrigenSeleccionada)) {
                unidadOrigenSeleccionada = unidadDefaultExistencia(rows, existenciaOrigenSeleccionada);
            }
            $("alm_prep_existencias").innerHTML = rows.map(function (exi) {
                var checked = String(exi.id_existencia_inventario) === String(existenciaOrigenSeleccionada) ? " checked" : "";
                var unidadDefault = unidadDefaultExistencia([exi], exi.id_existencia_inventario);
                var unidades = Array.isArray(exi.unidades_fisicas) ? exi.unidades_fisicas : [];
                var unidadesHtml = unidades.length ? "<div class=\"mt-3 ps-8\">" + unidades.map(function (unidad) {
                    var unidadChecked = String(unidad.id_inventario_unidad) === String(unidadOrigenSeleccionada) ? " checked" : "";
                    var codigo = unidad.codigo_etiqueta_interna || unidad.codigo_unico || ("Unidad " + unidad.id_inventario_unidad);
                    return "<label class=\"d-flex align-items-start gap-3 rounded border bg-light px-3 py-2 mb-2\">" +
                        "<input class=\"form-check-input mt-1\" type=\"radio\" name=\"alm_prep_unidad_origen\" data-unidad-origen=\"" + escapeHtml(unidad.id_inventario_unidad) + "\" data-unidad-existencia=\"" + escapeHtml(exi.id_existencia_inventario) + "\"" + unidadChecked + ">" +
                        "<span><span class=\"fw-bold\">" + escapeHtml(codigo) + "</span>" +
                        "<span class=\"d-block text-muted fs-8\">" + money(unidad.cantidad_base_disponible) + " " + escapeHtml(unidad.unidad_base || "") + " disponibles | " + escapeHtml(unidad.estado_fisico || "-") + "</span></span>" +
                        "</label>";
                }).join("") + "</div>" : "";
                return "<label class=\"border-bottom py-3 d-block\"><div class=\"d-flex align-items-start gap-3\">" +
                    "<input class=\"form-check-input mt-1\" type=\"radio\" name=\"alm_prep_existencia_origen\" data-existencia-origen=\"" + escapeHtml(exi.id_existencia_inventario) + "\" data-unidad-default=\"" + escapeHtml(unidadDefault) + "\"" + checked + ">" +
                    "<div><div class=\"fw-bold\">" + escapeHtml(exi.codigo_existencia || "-") + "</div>" +
                    "<div class=\"text-muted fs-8\">Lote " + escapeHtml(exi.lote || "-") + " | Cad. " + escapeHtml(exi.fecha_caducidad || "-") + "</div>" +
                    "<div class=\"text-muted fs-8\">Ubicacion " + escapeHtml(exi.ubicacion || "-") + "</div>" +
                    "<div class=\"badge badge-light-success mt-2\">Disponible " + money(exi.cantidad_disponible) + "</div></div></div>" +
                    unidadesHtml + "</label>";
            }).join("") || "<span class=\"text-muted\">Sin existencia origen disponible en el almacen seleccionado.</span>";
        }).catch(function (error) {
            $("alm_prep_existencias").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function unidadDefaultExistencia(rows, idExistencia) {
        var existencia = (rows || []).find(function (exi) { return String(exi.id_existencia_inventario) === String(idExistencia); });
        var unidades = existencia && Array.isArray(existencia.unidades_fisicas) ? existencia.unidades_fisicas : [];
        return unidades.length ? String(unidades[0].id_inventario_unidad) : "";
    }
    function unidadOrigenValida(rows, idUnidad) {
        if (!idUnidad) { return false; }
        return (rows || []).some(function (exi) {
            return (exi.unidades_fisicas || []).some(function (unidad) {
                return String(unidad.id_inventario_unidad) === String(idUnidad);
            });
        });
    }
    function cargarPreparaciones() {
        var params = new URLSearchParams({q: $("alm_prep_buscar").value.trim(), estatus: $("alm_prep_estado").value, solo_operativos: "1"});
        request("/almacen/preparaciones_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            preparaciones = response.depurar || [];
            renderPreparaciones();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    function renderPreparaciones() {
        $("alm_prep_body").innerHTML = preparaciones.map(function (item, index) {
            var badge = item.estatus === "confirmada" ? "badge-light-success" : (item.estatus === "cancelada" ? "badge-light-danger" : "badge-light-warning");
            var acciones = "<button class=\"btn btn-sm btn-light-primary me-2\" data-prep-editar=\"" + index + "\" type=\"button\"><i class=\"bi bi-pencil\"></i></button>";
            if (item.estatus === "borrador") {
                acciones += "<button class=\"btn btn-sm btn-light-success\" data-prep-confirmar=\"" + escapeHtml(item.id_preparacion_almacen) + "\" type=\"button\"><i class=\"bi bi-check2-circle\"></i></button>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_presentacion) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre_presentacion || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td class=\"text-end\">" + escapeHtml(item.unidades_preparadas) + "</td>" +
                "<td class=\"text-end\">" + money(item.cantidad_base_consumida) + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(item.estatus) + "</span></td>" +
                "<td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">Sin preparaciones</td></tr>";
    }
    function guardar() {
        var item = presentacionActual();
        if (!item) {
            Swal.fire({text: "Selecciona una presentacion.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        post("/almacen/preparacion_guardar_borrador_erp", {
            id_preparacion_almacen: $("alm_prep_id").value,
            id_almacen: $("alm_prep_almacen").value,
            id_sku_presentacion_regla: item.id_sku_presentacion_regla,
            id_sku_transformacion: item.id_sku_transformacion,
            id_existencia_origen: existenciaOrigenSeleccionada,
            id_unidad_origen: unidadOrigenSeleccionada,
            unidades_preparadas: $("alm_prep_unidades").value,
            observaciones: $("alm_prep_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            $("alm_prep_id").value = response.depurar.id_preparacion_almacen;
            $("alm_prep_confirmar").disabled = false;
            Swal.fire({text: response.mensaje + " " + response.depurar.folio, icon: "success", confirmButtonText: "Aceptar"});
            cargarPreparaciones();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    function confirmar(id) {
        Swal.fire({text: "Confirmar preparacion y afectar inventario.", icon: "warning", showCancelButton: true, confirmButtonText: "Confirmar", cancelButtonText: "Cancelar"}).then(function (result) {
            if (!result.isConfirmed) { return; }
            post("/almacen/preparacion_confirmar_erp", {id_preparacion_almacen: id}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                cargarPreparaciones();
                cargarPresentaciones();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }
    function editar(index) {
        var item = preparaciones[index];
        if (!item) { return; }
        $("alm_prep_id").value = item.id_preparacion_almacen;
        $("alm_prep_almacen").value = item.id_almacen;
        existenciaOrigenSeleccionada = item.id_existencia_origen || "";
        unidadOrigenSeleccionada = item.id_unidad_origen || "";
        $("alm_prep_unidades").value = item.unidades_preparadas;
        $("alm_prep_observaciones").value = item.observaciones || "";
        $("alm_prep_confirmar").disabled = item.estatus !== "borrador";
        cargarPresentaciones().then(function () {
            renderBases(item.id_sku_base);
            renderPresentacionesBase(item.id_sku_presentacion_regla, item.id_sku_transformacion);
            renderResumen();
            cargarExistenciasBase();
        });
    }
    function nuevo() {
        $("alm_prep_id").value = "";
        $("alm_prep_sku_base").value = "";
        existenciaOrigenSeleccionada = "";
        unidadOrigenSeleccionada = "";
        renderPresentacionesBase();
        $("alm_prep_presentacion").value = "";
        $("alm_prep_unidades").value = "1";
        $("alm_prep_observaciones").value = "";
        $("alm_prep_confirmar").disabled = true;
        renderResumen();
    }
    document.addEventListener("click", function (event) {
        var existencia = event.target.closest("[data-existencia-origen]");
        if (existencia) {
            existenciaOrigenSeleccionada = existencia.getAttribute("data-existencia-origen") || "";
            unidadOrigenSeleccionada = existencia.getAttribute("data-unidad-default") || "";
            if (unidadOrigenSeleccionada) {
                var unidadRadio = document.querySelector("[data-unidad-origen=\"" + unidadOrigenSeleccionada + "\"]");
                if (unidadRadio) { unidadRadio.checked = true; }
            }
        }
        var unidad = event.target.closest("[data-unidad-origen]");
        if (unidad) {
            unidadOrigenSeleccionada = unidad.getAttribute("data-unidad-origen") || "";
            existenciaOrigenSeleccionada = unidad.getAttribute("data-unidad-existencia") || existenciaOrigenSeleccionada;
            var existenciaRadio = document.querySelector("[data-existencia-origen=\"" + existenciaOrigenSeleccionada + "\"]");
            if (existenciaRadio) { existenciaRadio.checked = true; }
        }
        var confirmarBtn = event.target.closest("[data-prep-confirmar]");
        if (confirmarBtn) { confirmar(confirmarBtn.getAttribute("data-prep-confirmar")); }
        var editarBtn = event.target.closest("[data-prep-editar]");
        if (editarBtn) { editar(parseInt(editarBtn.getAttribute("data-prep-editar"), 10)); }
    });
    $("alm_prep_sku_base").addEventListener("change", alCambiarSkuBase);
    $("alm_prep_almacen").addEventListener("change", cargarPresentaciones);
    $("alm_prep_presentacion").addEventListener("change", function () { renderResumen(); cargarExistenciasBase(); });
    $("alm_prep_unidades").addEventListener("input", renderResumen);
    $("alm_prep_guardar").addEventListener("click", guardar);
    $("alm_prep_confirmar").addEventListener("click", function () { if ($("alm_prep_id").value) { confirmar($("alm_prep_id").value); } });
    $("alm_prep_recargar").addEventListener("click", cargarPreparaciones);
    $("alm_prep_buscar").addEventListener("input", function () { clearTimeout(window.__almPrepListado); window.__almPrepListado = setTimeout(cargarPreparaciones, 350); });
    $("alm_prep_estado").addEventListener("change", cargarPreparaciones);
    $("alm_prep_btn_nuevo").addEventListener("click", nuevo);

    cargarAlmacenes().then(function () {
        return cargarPresentaciones();
    }).then(cargarPreparaciones).catch(function (error) {
        Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
    });
})();
