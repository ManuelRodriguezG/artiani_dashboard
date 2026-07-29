"use strict";
(function () {
    var skusApertura = [];
    var recetaActual = null;
    var existenciasOrigen = [];

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: obtiene elementos DOM por id en la vista de apertura.
     * Impacto: UI de Almacen/Apertura de empaques.
     */
    function $(id) { return document.getElementById(id); }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: escapa texto dinamico antes de renderizarlo en HTML.
     * Impacto: UI de Almacen/Apertura de empaques; evita inyeccion accidental.
     */
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: ejecuta peticiones GET autenticadas al backend ERP.
     * Impacto: UI de Almacen/Apertura de empaques.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error("No se pudo leer la respuesta del servidor. Revisa sesion/permisos o recarga la pagina.");
                }
            });
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: ejecuta escrituras POST con CSRF para borradores y confirmaciones.
     * Impacto: Almacen/Apertura de empaques; mantiene el contrato seguro del ERP.
     */
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error("No se pudo leer la respuesta del servidor. Revisa sesion/permisos o recarga la pagina.");
                }
            });
        });
    }

    function money(value) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 6});
    }

    function aviso(text, icon) {
        if (window.Swal) {
            Swal.fire({text: text, icon: icon || "info", confirmButtonText: "Aceptar"});
            return;
        }
        alert(text);
    }

    function confirmarDialogo(text, callback) {
        if (window.Swal) {
            Swal.fire({text: text, icon: "warning", showCancelButton: true, confirmButtonText: "Confirmar", cancelButtonText: "Cancelar"}).then(function (result) {
                if (result.isConfirmed) { callback(); }
            });
            return;
        }
        if (confirm(text)) { callback(); }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: inicializa select2 si esta disponible en la plantilla.
     * Impacto: UI de Almacen/Apertura de empaques; mejora busqueda de ubicacion, SKU y unidad origen.
     */
    function refrescarSelectBuscable(id, placeholder) {
        var jq = window.jQuery;
        var element = $(id);
        if (!jq || !jq.fn || !jq.fn.select2 || !element) { return; }
        var select = jq(element);
        if (select.hasClass("select2-hidden-accessible")) {
            select.select2("destroy");
        }
        select.select2({placeholder: placeholder || "", allowClear: true, width: "100%"});
    }

    function opcionAlmacenPermiteApertura() {
        var select = $("alm_ape_almacen");
        var option = select.options[select.selectedIndex];
        return !!(option && option.getAttribute("data-apertura") === "1");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-28
     * Proposito: obtiene el SKU cerrado seleccionado por id real de configuracion, no por indice visual.
     * Impacto: UI de Almacen/Apertura; evita que Select2 o recargas del arreglo dejen el select sin referencia valida.
     */
    function skuActual() {
        var id = String($("alm_ape_sku_origen").value || "");
        if (!id) { return null; }
        for (var i = 0; i < skusApertura.length; i++) {
            var item = skusApertura[i] || {};
            if (String(item.id_apertura_catalogo || item.id_apertura_empaque || "") === id) {
                return item;
            }
        }
        return null;
    }

    function origenSeleccionado() {
        var value = $("alm_ape_unidad_origen").value || "";
        var parts = value.split("|");
        return {
            id_existencia_origen: parts[0] || "",
            id_unidad_origen: parts[1] || ""
        };
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: carga ubicaciones candidatas y marca cuales permiten apertura.
     * Impacto: UI de Almacen/Apertura de empaques; evita operar en tiendas/bodegas no autorizadas.
     */
    function cargarAlmacenes() {
        return request("/almacen/consultar_almacenes?incluir_inactivos=0").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var almacenes = response.depurar || [];
            $("alm_ape_almacen").innerHTML = "<option value=\"\">Selecciona lugar</option>" + almacenes.map(function (item) {
                var apertura = Number(item.permite_apertura_empaque || 0) === 1;
                var etiqueta = (item.codigo_almacen || item.id_almacen) + " - " + item.almacen + (apertura ? "" : " (no habilitado)");
                return "<option value=\"" + escapeHtml(item.id_almacen) + "\" data-apertura=\"" + (apertura ? "1" : "0") + "\">" + escapeHtml(etiqueta) + "</option>";
            }).join("");
            refrescarSelectBuscable("alm_ape_almacen", "Selecciona lugar");
            renderResumen();
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: carga SKUs cerrados configurados como empaques abribles.
     * Impacto: UI de Almacen/Apertura de empaques; impide abrir productos sin receta definida en Catalogo.
     */
    function cargarSkusApertura() {
        return request("/almacen/apertura_skus_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            skusApertura = response.depurar || [];
            $("alm_ape_sku_origen").innerHTML = "<option value=\"\">Selecciona SKU cerrado</option>" + skusApertura.map(function (item) {
                var id = item.id_apertura_catalogo || item.id_apertura_empaque || "";
                return "<option value=\"" + escapeHtml(id) + "\">" + escapeHtml(item.sku) + " - " + escapeHtml(item.nombre) + "</option>";
            }).join("");
            if (skusApertura.length === 0) {
                $("alm_ape_sku_origen").innerHTML = "<option value=\"\">Sin recetas de apertura configuradas</option>";
            }
            refrescarSelectBuscable("alm_ape_sku_origen", "Selecciona SKU cerrado");
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: consulta receta y existencias al seleccionar un SKU cerrado.
     * Impacto: UI de Almacen/Apertura de empaques; prepara captura multi-salida desde stock fisico.
     */
    function cargarReceta() {
        var sku = skuActual();
        recetaActual = null;
        existenciasOrigen = [];
        $("alm_ape_id").value = "";
        $("alm_ape_confirmar").disabled = true;
        $("alm_ape_resultados_body").innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-10\">Cargando configuracion de apertura...</td></tr>";
        renderUnidadesOrigen();
        if (!sku) {
            renderResumen();
            return Promise.resolve();
        }
        return request("/almacen/apertura_receta_erp?id_apertura_catalogo=" + encodeURIComponent(sku.id_apertura_catalogo || sku.id_apertura_empaque)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            recetaActual = response.depurar || null;
            renderComponentes();
            renderResumen();
            return cargarExistenciasOrigen();
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: consulta existencias disponibles para abrir en la ubicacion seleccionada.
     * Impacto: UI de Almacen/Apertura de empaques; obliga a elegir la unidad cerrada especifica.
     */
    function cargarExistenciasOrigen() {
        var sku = skuActual();
        existenciasOrigen = [];
        if (!sku || !$("alm_ape_almacen").value || !opcionAlmacenPermiteApertura()) {
            renderUnidadesOrigen();
            renderResumen();
            return Promise.resolve();
        }
        var params = new URLSearchParams({
            id_sku_origen: sku.id_sku_origen,
            id_almacen: $("alm_ape_almacen").value
        });
        return request("/almacen/apertura_existencias_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            existenciasOrigen = response.depurar || [];
            renderUnidadesOrigen();
            renderResumen();
        }).catch(function (error) {
            existenciasOrigen = [];
            renderUnidadesOrigen(error.message);
            renderResumen();
        });
    }

    function renderComponentes() {
        var componentes = recetaActual && Array.isArray(recetaActual.componentes) ? recetaActual.componentes : [];
        $("alm_ape_resultados_body").innerHTML = componentes.map(function (item) {
            return "<tr data-ape-componente=\"" + escapeHtml(item.id_componente) + "\">" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre) + "</div></td>" +
                "<td class=\"text-end\">" + escapeHtml(item.cantidad_esperada) + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end\" data-ape-cantidad=\"" + escapeHtml(item.id_componente) + "\" data-ape-sku-resultado=\"" + escapeHtml(item.id_sku_resultado || "") + "\" value=\"" + escapeHtml(item.cantidad_esperada) + "\" inputmode=\"decimal\"></td>" +
                "<td>" + escapeHtml(item.unidad || "pza") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-10\">Selecciona SKU cerrado con receta activa</td></tr>";
    }

    function renderUnidadesOrigen(errorMessage) {
        if (errorMessage) {
            $("alm_ape_unidad_origen").innerHTML = "<option value=\"\">" + escapeHtml(errorMessage) + "</option>";
            $("alm_ape_unidad_origen").disabled = true;
            refrescarSelectBuscable("alm_ape_unidad_origen", "Unidad fisica origen");
            return;
        }
        var opciones = [];
        existenciasOrigen.forEach(function (exi) {
            var unidades = Array.isArray(exi.unidades_fisicas) ? exi.unidades_fisicas : [];
            if (unidades.length) {
                unidades.forEach(function (unidad) {
                    var codigo = unidad.codigo_etiqueta_interna || unidad.codigo_unico || ("Unidad " + unidad.id_inventario_unidad);
                    opciones.push({
                        value: exi.id_existencia_inventario + "|" + unidad.id_inventario_unidad,
                        label: codigo + " | Lote " + (exi.lote || "-") + " | " + money(unidad.cantidad_base_disponible) + " " + (unidad.unidad_base || "")
                    });
                });
                return;
            }
            opciones.push({
                value: exi.id_existencia_inventario + "|",
                label: (exi.codigo_existencia || ("Existencia " + exi.id_existencia_inventario)) + " | Lote " + (exi.lote || "-") + " | Disponible " + money(exi.cantidad_disponible)
            });
        });
            $("alm_ape_unidad_origen").innerHTML = "<option value=\"\">Selecciona empaque fisico</option>" + opciones.map(function (item) {
                return "<option value=\"" + escapeHtml(item.value) + "\">" + escapeHtml(item.label) + "</option>";
            }).join("");
            if (!opciones.length) {
                $("alm_ape_unidad_origen").innerHTML = "<option value=\"\">Sin empaque cerrado disponible</option>";
            }
        $("alm_ape_unidad_origen").disabled = opciones.length === 0;
        refrescarSelectBuscable("alm_ape_unidad_origen", "Unidad fisica origen");
    }

    function capturarResultados() {
        return Array.prototype.slice.call(document.querySelectorAll("[data-ape-cantidad]")).map(function (input) {
            return {
                id_componente: input.getAttribute("data-ape-cantidad"),
                id_sku_resultado: input.getAttribute("data-ape-sku-resultado"),
                cantidad_real: input.value
            };
        });
    }

    function puedeGuardar() {
        return !!(opcionAlmacenPermiteApertura() && skuActual() && recetaActual && origenSeleccionado().id_existencia_origen);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: muestra estado operativo y habilita acciones solo con datos completos.
     * Impacto: UI de Almacen/Apertura de empaques; reduce riesgo de entradas vendibles incorrectas.
     */
    function renderResumen() {
        var accionesDisponibles = puedeGuardar();
        $("alm_ape_guardar").disabled = !accionesDisponibles;
        $("alm_ape_confirmar").disabled = !$("alm_ape_id").value;
        if (!$("alm_ape_almacen").value) {
            $("alm_ape_resumen").innerHTML = "Selecciona un SKU cerrado con receta de apertura.";
            return;
        }
        if (!opcionAlmacenPermiteApertura()) {
            $("alm_ape_resumen").innerHTML = "<span class=\"fw-bold text-warning\">Lugar no habilitado para apertura.</span><div class=\"text-muted fs-7 mt-1\">Puedes revisar recetas, pero para guardar se debe habilitar Apertura empaque en la configuracion del lugar donde fisicamente se abrira.</div>";
            return;
        }
        var sku = skuActual();
        if (!sku) {
            $("alm_ape_resumen").innerHTML = "<span class=\"fw-bold text-success\">Lugar habilitado.</span><div class=\"text-muted fs-7 mt-1\">Selecciona un SKU cerrado con receta de apertura.</div>";
            return;
        }
        var origen = origenSeleccionado();
        var textoOrigen = origen.id_existencia_origen ? "Empaque origen seleccionado" : "Falta seleccionar empaque fisico";
        $("alm_ape_resumen").innerHTML = "<div class=\"fw-bold\">" + escapeHtml(sku.sku) + "</div>" +
            "<div class=\"text-muted fs-7\">" + escapeHtml(sku.nombre || "") + "</div>" +
            "<div class=\"mt-3 d-flex flex-wrap gap-2\">" +
            "<span class=\"badge badge-light-primary\">Destino granel</span>" +
            "<span class=\"badge badge-light-info\">Esperado " + escapeHtml(sku.cantidad_total_esperada || 0) + "</span>" +
            "<span class=\"badge " + (origen.id_existencia_origen ? "badge-light-success" : "badge-light-warning") + "\">" + escapeHtml(textoOrigen) + "</span>" +
            "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: guarda el folio APE en borrador con cantidades reales por componente.
     * Impacto: Almacen/Apertura de empaques; no afecta inventario hasta confirmar.
     */
    function guardar() {
        var sku = skuActual();
        var origen = origenSeleccionado();
        if (!puedeGuardar()) {
            aviso("Selecciona lugar habilitado, SKU cerrado, receta y empaque fisico origen.", "warning");
            return;
        }
        post("/almacen/apertura_guardar_borrador_erp", {
            id_apertura_empaque: $("alm_ape_id").value,
            id_almacen: $("alm_ape_almacen").value,
            id_apertura_catalogo: sku.id_apertura_catalogo || sku.id_apertura_empaque,
            id_existencia_origen: origen.id_existencia_origen,
            id_unidad_origen: origen.id_unidad_origen,
            resultados: JSON.stringify(capturarResultados()),
            observaciones: $("alm_ape_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            $("alm_ape_id").value = response.depurar.id_apertura_empaque;
            $("alm_ape_confirmar").disabled = false;
            aviso(response.mensaje + " " + response.depurar.folio, "success");
            cargarAperturas();
        }).catch(function (error) {
            aviso(error.message, "error");
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: confirma apertura y aplica kardex de salida/entrada.
     * Impacto: Almacen/Inventario; consume una unidad cerrada y crea existencias de piezas internas.
     */
    function confirmarApertura(id) {
        confirmarDialogo("Confirmar apertura y afectar inventario.", function () {
            post("/almacen/apertura_confirmar_erp", {id_apertura_empaque: id}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                aviso(response.mensaje, "success");
                $("alm_ape_id").value = "";
                $("alm_ape_confirmar").disabled = true;
                cargarExistenciasOrigen();
                cargarAperturas();
            }).catch(function (error) {
                aviso(error.message, "error");
            });
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-25
     * Proposito: lista folios de apertura para seguimiento operativo.
     * Impacto: UI de Almacen/Apertura de empaques; permite confirmar borradores existentes.
     */
    function cargarAperturas() {
        var params = new URLSearchParams({
            q: $("alm_ape_buscar").value.trim(),
            estatus: $("alm_ape_estado").value
        });
        return request("/almacen/aperturas_empaque_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var aperturas = response.depurar || [];
            $("alm_ape_body").innerHTML = aperturas.map(function (item) {
                var badge = item.estatus === "confirmada" ? "badge-light-success" : (item.estatus === "cancelada" ? "badge-light-danger" : "badge-light-warning");
                var acciones = item.estatus === "borrador"
                    ? "<button class=\"btn btn-sm btn-light-success\" data-ape-confirmar=\"" + escapeHtml(item.id_apertura_empaque) + "\" type=\"button\"><i class=\"bi bi-check2-circle\"></i></button>"
                    : "<button class=\"btn btn-sm btn-light\" type=\"button\" disabled><i class=\"bi bi-eye\"></i></button>";
                return "<tr>" +
                    "<td><span class=\"fw-bold\">" + escapeHtml(item.folio) + "</span></td>" +
                    "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_origen) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_origen) + "</div></td>" +
                    "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                    "<td class=\"text-end\">" + escapeHtml(item.total_resultados || item.cantidad_resultado_total || 0) + "</td>" +
                    "<td><span class=\"badge " + badge + "\">" + escapeHtml(item.estatus) + "</span></td>" +
                    "<td class=\"text-end\">" + acciones + "</td>" +
                    "</tr>";
            }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin aperturas</td></tr>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-28
     * Proposito: enlaza cambios nativos y Select2 sin depender de un solo mecanismo de eventos.
     * Impacto: UI de Almacen/Apertura; asegura que la carga de receta/existencias se dispare al seleccionar opciones.
     */
    function vincularCambioSelect(id, handler) {
        var element = $(id);
        if (!element) { return; }
        element.addEventListener("change", handler);
        if (window.jQuery) {
            window.jQuery(element).off("change.almApe").on("change.almApe", handler);
            window.jQuery(element).off("select2:select.almApe").on("select2:select.almApe", handler);
            window.jQuery(element).off("select2:clear.almApe").on("select2:clear.almApe", handler);
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        vincularCambioSelect("alm_ape_almacen", function () {
            $("alm_ape_id").value = "";
            $("alm_ape_confirmar").disabled = true;
            cargarExistenciasOrigen();
        });
        vincularCambioSelect("alm_ape_sku_origen", function () {
            cargarReceta().catch(function (error) {
                $("alm_ape_resumen").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
            });
        });
        vincularCambioSelect("alm_ape_unidad_origen", renderResumen);
        $("alm_ape_guardar").addEventListener("click", guardar);
        $("alm_ape_confirmar").addEventListener("click", function () {
            if ($("alm_ape_id").value) { confirmarApertura($("alm_ape_id").value); }
        });
        $("alm_ape_recargar").addEventListener("click", function () {
            cargarAperturas().catch(function (error) {
                $("alm_ape_body").innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-danger py-10\">" + escapeHtml(error.message) + "</td></tr>";
            });
        });
        $("alm_ape_estado").addEventListener("change", cargarAperturas);
        $("alm_ape_buscar").addEventListener("input", function () {
            clearTimeout(window.__almApeBuscar);
            window.__almApeBuscar = setTimeout(cargarAperturas, 300);
        });
        document.addEventListener("click", function (event) {
            var confirmarBtn = event.target.closest("[data-ape-confirmar]");
            if (confirmarBtn) { confirmarApertura(confirmarBtn.getAttribute("data-ape-confirmar")); }
        });
        Promise.all([cargarAlmacenes(), cargarSkusApertura(), cargarAperturas()]).catch(function (error) {
            $("alm_ape_resumen").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    });
})();
