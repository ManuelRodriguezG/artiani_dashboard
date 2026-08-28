"use strict";

(function () {
    var paquetes = [];
    var catalogos = {};
    var paqueteActualId = 0;
    var componentesForm = [];
    var permisos = window.CATALOGO_PERMISOS || {};

    function request(url, data) {
        var body = null;
        if (data) {
            var params = new URLSearchParams();
            Object.keys(data).forEach(function (key) {
                if (Array.isArray(data[key])) {
                    data[key].forEach(function (value) { params.append(key, value); });
                } else {
                    params.append(key, data[key]);
                }
            });
            body = params.toString();
        }
        return fetch(url, {method: data ? "POST" : "GET", headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {}, body: body, credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) { return String(value === null || value === undefined ? "" : value).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
    function escapeAttr(value) { return escapeHtml(value).replace(/`/g, "&#096;"); }
    function el(id) { return document.getElementById(id); }
    function mostrarError(error) { var box = el("paquetes_error"); if (!box) { return; } box.textContent = error && error.message ? error.message : String(error || "No se pudo completar la accion"); box.classList.remove("d-none"); }
    function limpiarError() { var box = el("paquetes_error"); if (box) { box.textContent = ""; box.classList.add("d-none"); } }
    function avisar(mensaje, icono) { if (window.Swal) { Swal.fire({text: mensaje, icon: icono || "success", confirmButtonText: "Aceptar"}); } else { window.alert(mensaje); } }
    function setValor(form, name, value) { var field = form ? form.querySelector("[name='" + name + "']") : null; if (field) { field.value = value === null || value === undefined ? "" : value; } }
    function setChecked(form, name, value) { var field = form ? form.querySelector("[name='" + name + "']") : null; if (field) { field.checked = Number(value || 0) === 1; } }
    function serializarForm(form) { var data = {}; new FormData(form).forEach(function (value, key) { if (Object.prototype.hasOwnProperty.call(data, key)) { if (!Array.isArray(data[key])) { data[key] = [data[key]]; } data[key].push(value); } else { data[key] = value; } }); return data; }

    function llenarUnidades() {
        var unidades = catalogos.unidades || [];
        ["paquete_sku_unidad", "paquete_opcion_unidad"].forEach(function (id) {
            var select = el(id);
            if (!select) { return; }
            var base = id === "paquete_opcion_unidad" ? "<option value=\"\">Base SKU</option>" : "<option value=\"\">Selecciona unidad</option>";
            select.innerHTML = base + unidades.map(function (unidad) {
                var label = (unidad.nombre || "") + (unidad.abreviatura ? " (" + unidad.abreviatura + ")" : "");
                return "<option value=\"" + escapeAttr(unidad.id_unidad) + "\">" + escapeHtml(label) + "</option>";
            }).join("");
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-28
     * Proposito: cargar paquetes y catalogos auxiliares para la pantalla dedicada de paquetes.
     * Impacto: Catalogo ERP; desacopla recetas del modal de productos sin tocar Ventas, Inventario ni Rentabilidad.
     * Contrato: consume /catalogoerp/paquetes_listar y /catalogoerp/catalogos; ambos devuelven JSON ERP con depurar.
     */
    function cargar() {
        limpiarError();
        var q = encodeURIComponent((el("paquetes_buscar") || {}).value || "");
        var estatus = encodeURIComponent((el("paquetes_estatus") || {}).value || "activos");
        Promise.all([request("/catalogoerp/paquetes_listar?q=" + q + "&estatus=" + estatus + "&limite=150"), request("/catalogoerp/catalogos")]).then(function (responses) {
            if (responses[0].error && !(responses[0].depurar && responses[0].depurar.paquetes)) { throw new Error(responses[0].mensaje); }
            paquetes = (responses[0].depurar && responses[0].depurar.paquetes) || [];
            catalogos = responses[1].depurar || {};
            llenarUnidades();
            renderLista();
            if (paqueteActualId) { seleccionarPaquete(paqueteActualId, true); } else { renderDetalle(); }
        }).catch(mostrarError);
    }

    function renderLista() {
        var lista = el("paquetes_lista");
        var total = el("paquetes_total");
        if (total) { total.textContent = String(paquetes.length); }
        if (!lista) { return; }
        if (!paquetes.length) { lista.innerHTML = "<div class=\"text-muted text-center py-8\">No hay paquetes con esos filtros.</div>"; return; }
        lista.innerHTML = paquetes.map(function (paquete) {
            var activo = Number(paquete.id_paquete) === Number(paqueteActualId) ? " is-active" : "";
            var grupos = (paquete.grupos || []).length;
            var componentes = (paquete.componentes || []).length;
            return "<div class=\"catalogo-paquete-card" + activo + "\"><div class=\"d-flex justify-content-between gap-3\"><div><div class=\"fw-bold\">" + escapeHtml(paquete.sku) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(paquete.nombre_sku || paquete.producto || "") + "</div></div><span class=\"badge badge-light-" + (paquete.estatus === "activo" ? "success" : "warning") + "\">" + escapeHtml(paquete.estatus) + "</span></div><div class=\"d-flex flex-wrap gap-2 mt-3 fs-8 text-muted\"><span>" + escapeHtml(paquete.tipo_paquete) + "</span><span>Componentes: " + componentes + "</span><span>Grupos: " + grupos + "</span></div><div class=\"d-flex gap-2 mt-4\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-editar-paquete=\"" + escapeAttr(paquete.id_paquete) + "\">Editar</button>" + (permisos.editar ? "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-eliminar-paquete=\"" + escapeAttr(paquete.id_paquete) + "\">Eliminar</button>" : "") + "</div></div>";
        }).join("");
    }

    function paquetePorId(idPaquete) { return paquetes.find(function (item) { return Number(item.id_paquete) === Number(idPaquete); }) || null; }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-28
     * Proposito: cargar una receta en el editor dedicado sin abrir el modal de producto.
     * Impacto: Catalogo ERP; permite corregir paquetes complejos desde una sola pantalla operativa.
     */
    function seleccionarPaquete(idPaquete, silencioso) {
        var paquete = paquetePorId(idPaquete);
        if (!paquete) { if (!silencioso) { avisar("No se encontro el paquete seleccionado", "warning"); } paqueteActualId = 0; renderLista(); renderDetalle(); return; }
        paqueteActualId = Number(paquete.id_paquete);
        var form = el("paquete_form_receta");
        setValor(form, "id_paquete", paquete.id_paquete);
        actualizarSelectSku("paquete_sku", paquete.id_sku_paquete, paquete.sku, paquete.nombre_sku);
        setValor(form, "tipo_paquete", paquete.tipo_paquete || "simple");
        setValor(form, "modo_disponibilidad", paquete.modo_disponibilidad || "por_componentes");
        setValor(form, "estatus", paquete.estatus || "activo");
        setValor(form, "observaciones", paquete.observaciones || "");
        setChecked(form, "permite_configuracion_cliente", paquete.permite_configuracion_cliente);
        setChecked(form, "requiere_armado_almacen", paquete.requiere_armado_almacen);
        setChecked(form, "permite_desarmar", paquete.permite_desarmar);
        componentesForm = (paquete.componentes || []).map(function (item) { return {id_sku: item.id_sku_componente, sku: item.sku, nombre: item.nombre_sku, cantidad: item.cantidad || 1, id_unidad: item.id_unidad || "", factor: item.factor_conversion || 1}; });
        prepararGrupo(null);
        prepararOpcion(null, null);
        renderComponentes();
        renderDetalle();
        renderLista();
        if (el("paquete_editor_titulo")) { el("paquete_editor_titulo").textContent = "Editar paquete"; }
    }

    function renderDetalle() {
        var box = el("paquete_detalle_actual");
        if (!box) { return; }
        var paquete = paqueteActualId ? paquetePorId(paqueteActualId) : null;
        if (!paquete) { box.innerHTML = "Selecciona un paquete para revisar su receta."; return; }
        var componentes = (paquete.componentes || []).map(function (item) { return "<li>" + escapeHtml(item.cantidad) + " x " + escapeHtml(item.sku) + " " + escapeHtml(item.nombre_sku || "") + "</li>"; }).join("") || "<li class=\"text-muted\">Sin componentes fijos.</li>";
        var grupos = (paquete.grupos || []).map(function (grupo) {
            var opciones = (grupo.opciones || []).map(function (opcion) {
                return "<li><span class=\"fw-semibold\">" + escapeHtml(opcion.sku) + "</span> " + escapeHtml(opcion.nombre_sku || "") + (permisos.editar ? " <button class=\"btn btn-xs btn-light-primary ms-2\" type=\"button\" data-editar-opcion=\"" + escapeAttr(opcion.id_opcion) + "\">Editar</button> <button class=\"btn btn-xs btn-light-danger\" type=\"button\" data-eliminar-opcion=\"" + escapeAttr(opcion.id_opcion) + "\">Eliminar</button>" : "") + "</li>";
            }).join("") || "<li class=\"text-muted\">Sin opciones.</li>";
            return "<div class=\"border rounded p-4 mt-3\"><div class=\"d-flex justify-content-between gap-3\"><div><div class=\"fw-semibold\">" + escapeHtml(grupo.nombre) + "</div><div class=\"text-muted fs-8\">Min " + escapeHtml(grupo.min_selecciones) + " / Max " + escapeHtml(grupo.max_selecciones) + "</div></div>" + (permisos.editar ? "<div class=\"d-flex gap-2\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-editar-grupo=\"" + escapeAttr(grupo.id_grupo) + "\">Editar</button><button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-eliminar-grupo=\"" + escapeAttr(grupo.id_grupo) + "\">Eliminar</button></div>" : "") + "</div><ul class=\"mb-0 mt-3\">" + opciones + "</ul>" + (permisos.editar ? "<button class=\"btn btn-sm btn-light-primary mt-3\" type=\"button\" data-nueva-opcion=\"" + escapeAttr(grupo.id_grupo) + "\">Agregar opcion</button>" : "") + "</div>";
        }).join("") || "<div class=\"text-muted\">Sin grupos configurables.</div>";
        box.innerHTML = "<div class=\"fw-bold mb-1\">" + escapeHtml(paquete.sku) + "</div><div class=\"text-muted mb-4\">" + escapeHtml(paquete.nombre_sku || "") + "</div><div class=\"fw-semibold mb-2\">Componentes fijos</div><ul>" + componentes + "</ul><div class=\"fw-semibold mt-5 mb-2\">Grupos configurables</div>" + grupos;
        llenarSelectGrupos(paquete.grupos || []);
    }

    function llenarSelectGrupos(grupos) {
        var select = el("paquete_opcion_grupo");
        if (!select) { return; }
        select.innerHTML = "<option value=\"\">Selecciona grupo</option>" + grupos.map(function (grupo) { return "<option value=\"" + escapeAttr(grupo.id_grupo) + "\">" + escapeHtml(grupo.nombre) + "</option>"; }).join("");
    }

    function actualizarSelectSku(idSelect, idSku, sku, nombre) {
        var select = el(idSelect);
        if (!select || !idSku) { return; }
        select.innerHTML = "<option value=\"" + escapeAttr(idSku) + "\">" + escapeHtml(sku + " - " + (nombre || "")) + "</option>";
        select.value = String(idSku);
    }

    function renderComponentes() {
        var tbody = el("paquete_componentes");
        if (!tbody) { return; }
        if (!componentesForm.length) { tbody.innerHTML = "<tr><td colspan=\"5\" class=\"text-muted text-center py-5\">Busca y agrega componentes fijos.</td></tr>"; return; }
        var unidades = catalogos.unidades || [];
        tbody.innerHTML = componentesForm.map(function (item, index) {
            var opciones = "<option value=\"\">Base SKU</option>" + unidades.map(function (unidad) { var selected = String(unidad.id_unidad) === String(item.id_unidad || "") ? " selected" : ""; return "<option value=\"" + escapeAttr(unidad.id_unidad) + "\"" + selected + ">" + escapeHtml(unidad.abreviatura || unidad.nombre || "") + "</option>"; }).join("");
            return "<tr data-componente-row=\"" + index + "\"><td><div class=\"fw-semibold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div></td><td><input class=\"form-control form-control-sm\" type=\"number\" min=\"0.000001\" step=\"0.000001\" data-componente-cantidad=\"" + index + "\" value=\"" + escapeAttr(item.cantidad || 1) + "\"></td><td><select class=\"form-select form-select-sm\" data-componente-unidad=\"" + index + "\">" + opciones + "</select></td><td><input class=\"form-control form-control-sm\" type=\"number\" min=\"0.000001\" step=\"0.000001\" data-componente-factor=\"" + index + "\" value=\"" + escapeAttr(item.factor || 1) + "\"></td><td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" data-quitar-componente=\"" + index + "\" title=\"Eliminar componente\"><i class=\"bi bi-trash\"></i></button></td></tr>";
        }).join("");
    }

    function sincronizarComponentesDesdeTabla() {
        componentesForm.forEach(function (item, index) {
            var cantidad = document.querySelector("[data-componente-cantidad='" + index + "']");
            var unidad = document.querySelector("[data-componente-unidad='" + index + "']");
            var factor = document.querySelector("[data-componente-factor='" + index + "']");
            if (cantidad) { item.cantidad = cantidad.value; }
            if (unidad) { item.id_unidad = unidad.value; }
            if (factor) { item.factor = factor.value; }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-28
     * Proposito: buscar SKUs operativos para paquete, componente u opcion configurable desde la pantalla dedicada.
     * Impacto: Catalogo ERP; evita mostrar SKUs inactivos/fusionados y reduce errores de armado.
     */
    function buscarSku(tipo) {
        var inputId = tipo === "paquete" ? "paquete_buscar_sku" : (tipo === "opcion" ? "paquete_buscar_opcion" : "paquete_buscar_componente");
        var resultId = tipo === "paquete" ? "paquete_resultados_sku" : (tipo === "opcion" ? "paquete_resultados_opcion" : "paquete_resultados_componente");
        var input = el(inputId);
        var result = el(resultId);
        var termino = input ? input.value.trim() : "";
        if (!result) { return; }
        if (termino.length < 2) { result.innerHTML = "<div class=\"text-muted fs-8\">Captura al menos dos caracteres.</div>"; return; }
        result.innerHTML = "<div class=\"text-muted fs-8\">Buscando...</div>";
        request("/catalogoerp/paquetes_buscar_skus?q=" + encodeURIComponent(termino) + "&limite=30").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var items = response.depurar || [];
            if (!items.length) { result.innerHTML = "<div class=\"text-muted fs-8\">Sin resultados.</div>"; return; }
            result.innerHTML = items.map(function (item) { return "<button class=\"btn btn-sm btn-light-primary me-2 mb-2\" type=\"button\" data-seleccionar-sku=\"" + escapeAttr(item.id_sku) + "\" data-tipo=\"" + escapeAttr(tipo) + "\" data-sku=\"" + escapeAttr(item.sku) + "\" data-nombre=\"" + escapeAttr(item.nombre || item.producto || "") + "\">" + escapeHtml(item.sku + " - " + (item.nombre || item.producto || "")) + "</button>"; }).join("");
        }).catch(function (error) { result.innerHTML = ""; mostrarError(error); });
    }

    function seleccionarSkuBoton(button) {
        var tipo = button.getAttribute("data-tipo");
        var idSku = button.getAttribute("data-seleccionar-sku");
        var sku = button.getAttribute("data-sku") || "";
        var nombre = button.getAttribute("data-nombre") || "";
        if (tipo === "paquete") { actualizarSelectSku("paquete_sku", idSku, sku, nombre); return; }
        if (tipo === "opcion") { actualizarSelectSku("paquete_opcion_sku", idSku, sku, nombre); return; }
        if (componentesForm.some(function (item) { return String(item.id_sku) === String(idSku); })) { avisar("Ese SKU ya esta como componente fijo. Ajusta su cantidad en la tabla.", "warning"); return; }
        componentesForm.push({id_sku: idSku, sku: sku, nombre: nombre, cantidad: 1, id_unidad: "", factor: 1});
        renderComponentes();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-28
     * Proposito: crear un SKU paquete minimo desde la seccion dedicada sin abrir el modal completo de producto.
     * Impacto: Catalogo ERP; mantiene el paquete como producto tipo kit y deja costo/precio para Rentabilidad/Listas.
     */
    function guardarSkuPaquete(event) {
        event.preventDefault();
        limpiarError();
        var form = event.currentTarget;
        var data = serializarForm(form);
        data.codigo_producto = data.sku;
        data.nombre_producto = data.nombre;
        data.nombre_sku = data.nombre;
        data.descripcion = "Producto paquete creado desde Catalogo > Paquetes";
        data.tipo_producto = "kit";
        data.tipo_inventario = "kit";
        data.factor_unidad_base = "1";
        data.costo_referencia = "0";
        data.estatus = "borrador";
        data.stock_minimo = "0";
        data.stock_maximo = "";
        data.punto_reorden = "0";
        data.estrategia_salida = "FIFO";
        data.permite_venta_sin_existencia = "0";
        data.precision_decimal = "0";
        data.incremento_minimo_venta = "1";
        data.unidad_venta_label = "";
        var button = form.querySelector("[type='submit']");
        if (button) { button.disabled = true; }
        request("/catalogoerp/registrar", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            avisar(response.mensaje || "SKU paquete creado", "success");
            actualizarSelectSku("paquete_sku", response.depurar.id_sku, data.sku, data.nombre);
            form.reset();
            cargar();
        }).catch(mostrarError).finally(function () { if (button) { button.disabled = false; } });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-28
     * Proposito: guardar receta de paquete desde la seccion dedicada enviando arreglos PHP reales de componentes.
     * Impacto: Catalogo ERP; dispara la incidencia de costo derivado ya existente para Rentabilidad.
     */
    function guardarReceta(event) {
        event.preventDefault();
        limpiarError();
        sincronizarComponentesDesdeTabla();
        var form = event.currentTarget;
        var data = serializarForm(form);
        componentesForm.forEach(function (item) {
            if (!item.id_sku) { return; }
            if (!data.componente_sku) { data.componente_sku = []; }
            if (!data.componente_cantidad) { data.componente_cantidad = []; }
            if (!data.componente_unidad) { data.componente_unidad = []; }
            if (!data.componente_factor) { data.componente_factor = []; }
            data.componente_sku.push(item.id_sku);
            data.componente_cantidad.push(item.cantidad || 1);
            data.componente_unidad.push(item.id_unidad || "");
            data.componente_factor.push(item.factor || 1);
        });
        var button = form.querySelector("[type='submit']");
        if (button) { button.disabled = true; }
        request("/catalogoerp/guardar_paquete_simple", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            avisar(response.mensaje || "Paquete guardado", "success");
            paqueteActualId = response.depurar && response.depurar.id_paquete ? Number(response.depurar.id_paquete) : paqueteActualId;
            cargar();
        }).catch(mostrarError).finally(function () { if (button) { button.disabled = false; } });
    }

    function prepararGrupo(grupo) {
        var form = el("paquete_form_grupo");
        if (!form) { return; }
        form.reset();
        setValor(form, "id_paquete", paqueteActualId || "");
        setValor(form, "id_grupo", grupo ? grupo.id_grupo : "");
        setValor(form, "codigo", grupo ? grupo.codigo : "");
        setValor(form, "nombre", grupo ? grupo.nombre : "");
        setValor(form, "descripcion", grupo ? grupo.descripcion : "");
        setValor(form, "min_selecciones", grupo ? grupo.min_selecciones : 1);
        setValor(form, "max_selecciones", grupo ? grupo.max_selecciones : 1);
        setValor(form, "modo_cantidad", grupo ? grupo.modo_cantidad : "cantidad_fija");
        setValor(form, "cantidad_total_grupo", grupo ? grupo.cantidad_total_grupo : "");
        setValor(form, "orden", grupo ? grupo.orden : 0);
        setValor(form, "estatus", grupo ? grupo.estatus : "activo");
        setChecked(form, "obligatorio", grupo ? grupo.obligatorio : 1);
    }

    function prepararOpcion(idGrupo, opcion) {
        var form = el("paquete_form_opcion");
        if (!form) { return; }
        form.reset();
        setValor(form, "id_grupo", idGrupo || (opcion ? opcion.id_grupo : ""));
        setValor(form, "id_opcion", opcion ? opcion.id_opcion : "");
        if (el("paquete_opcion_grupo")) { el("paquete_opcion_grupo").value = idGrupo || (opcion ? opcion.id_grupo : ""); }
        if (opcion) { actualizarSelectSku("paquete_opcion_sku", opcion.id_sku_opcion, opcion.sku, opcion.nombre_sku); }
        else if (el("paquete_opcion_sku")) { el("paquete_opcion_sku").innerHTML = "<option value=\"\">Selecciona desde busqueda</option>"; }
        setValor(form, "cantidad_default", opcion ? opcion.cantidad_default : 1);
        setValor(form, "cantidad_minima", opcion ? opcion.cantidad_minima : "");
        setValor(form, "cantidad_maxima", opcion ? opcion.cantidad_maxima : "");
        setValor(form, "id_unidad", opcion ? opcion.id_unidad : "");
        setValor(form, "factor_conversion", opcion ? opcion.factor_conversion : 1);
        setValor(form, "orden", opcion ? opcion.orden : 0);
        setValor(form, "estatus", opcion ? opcion.estatus : "activo");
        setChecked(form, "permite_cantidad_editable", opcion ? opcion.permite_cantidad_editable : 0);
    }

    function guardarFormulario(endpoint, form, recargarId) {
        limpiarError();
        var button = form.querySelector("[type='submit']");
        if (button) { button.disabled = true; }
        request(endpoint, serializarForm(form)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            avisar(response.mensaje || "Guardado", "success");
            if (recargarId) { paqueteActualId = recargarId; }
            cargar();
        }).catch(mostrarError).finally(function () { if (button) { button.disabled = false; } });
    }

    function encontrarGrupo(idGrupo) { var paquete = paquetePorId(paqueteActualId); return ((paquete && paquete.grupos) || []).find(function (grupo) { return Number(grupo.id_grupo) === Number(idGrupo); }) || null; }
    function encontrarOpcion(idOpcion) {
        var encontrada = null;
        ((paquetePorId(paqueteActualId) || {}).grupos || []).forEach(function (grupo) { (grupo.opciones || []).forEach(function (opcion) { if (Number(opcion.id_opcion) === Number(idOpcion)) { encontrada = opcion; } }); });
        return encontrada;
    }

    function eliminar(endpoint, data, mensaje) {
        var ejecutar = function () { request(endpoint, data).then(function (response) { if (response.error) { throw new Error(response.mensaje); } avisar(response.mensaje || "Eliminado", "success"); cargar(); }).catch(mostrarError); };
        if (window.Swal) { Swal.fire({text: mensaje, icon: "warning", showCancelButton: true, confirmButtonText: "Eliminar", cancelButtonText: "Cancelar"}).then(function (result) { if (result.isConfirmed) { ejecutar(); } }); }
        else if (window.confirm(mensaje)) { ejecutar(); }
    }

    function limpiarEditor() {
        paqueteActualId = 0;
        componentesForm = [];
        ["paquete_form_sku", "paquete_form_receta", "paquete_form_grupo", "paquete_form_opcion"].forEach(function (id) { var form = el(id); if (form) { form.reset(); } });
        if (el("paquete_sku")) { el("paquete_sku").innerHTML = "<option value=\"\">Selecciona desde busqueda o crea uno nuevo</option>"; }
        if (el("paquete_opcion_sku")) { el("paquete_opcion_sku").innerHTML = "<option value=\"\">Selecciona desde busqueda</option>"; }
        limpiarError();
        renderComponentes();
        renderDetalle();
        renderLista();
        if (el("paquete_editor_titulo")) { el("paquete_editor_titulo").textContent = "Nuevo paquete"; }
    }

    function bind() {
        var buscar = el("paquetes_buscar");
        if (buscar) { var timer = null; buscar.addEventListener("input", function () { window.clearTimeout(timer); timer = window.setTimeout(cargar, 350); }); }
        if (el("paquetes_estatus")) { el("paquetes_estatus").addEventListener("change", cargar); }
        if (el("paquetes_nuevo")) { el("paquetes_nuevo").addEventListener("click", limpiarEditor); }
        if (el("paquetes_limpiar")) { el("paquetes_limpiar").addEventListener("click", limpiarEditor); }
        if (el("paquete_form_sku")) { el("paquete_form_sku").addEventListener("submit", guardarSkuPaquete); }
        if (el("paquete_form_receta")) { el("paquete_form_receta").addEventListener("submit", guardarReceta); }
        if (el("paquete_form_grupo")) { el("paquete_form_grupo").addEventListener("submit", function (event) { event.preventDefault(); if (!paqueteActualId && !event.currentTarget.querySelector("[name='id_paquete']").value) { mostrarError(new Error("Primero guarda o selecciona una receta de paquete.")); return; } guardarFormulario("/catalogoerp/guardar_paquete_grupo", event.currentTarget, paqueteActualId); }); }
        if (el("paquete_form_opcion")) { el("paquete_form_opcion").addEventListener("submit", function (event) { event.preventDefault(); if (el("paquete_opcion_grupo") && el("paquete_opcion_grupo").value) { setValor(event.currentTarget, "id_grupo", el("paquete_opcion_grupo").value); } guardarFormulario("/catalogoerp/guardar_paquete_opcion", event.currentTarget, paqueteActualId); }); }
        [["paquete_buscar_sku_btn", "paquete"], ["paquete_buscar_componente_btn", "componente"], ["paquete_buscar_opcion_btn", "opcion"]].forEach(function (pair) { if (el(pair[0])) { el(pair[0]).addEventListener("click", function () { buscarSku(pair[1]); }); } });
        [["paquete_buscar_sku", "paquete"], ["paquete_buscar_componente", "componente"], ["paquete_buscar_opcion", "opcion"]].forEach(function (pair) { if (el(pair[0])) { el(pair[0]).addEventListener("keydown", function (event) { if (event.key === "Enter") { event.preventDefault(); buscarSku(pair[1]); } }); } });
        document.addEventListener("click", function (event) {
            var seleccionar = event.target.closest("[data-seleccionar-sku]"); if (seleccionar) { seleccionarSkuBoton(seleccionar); return; }
            var editar = event.target.closest("[data-editar-paquete]"); if (editar) { seleccionarPaquete(editar.getAttribute("data-editar-paquete")); return; }
            var quitar = event.target.closest("[data-quitar-componente]"); if (quitar) { componentesForm.splice(Number(quitar.getAttribute("data-quitar-componente")), 1); renderComponentes(); return; }
            var eliminarPaquete = event.target.closest("[data-eliminar-paquete]"); if (eliminarPaquete) { eliminar("/catalogoerp/eliminar_paquete", {id_paquete: eliminarPaquete.getAttribute("data-eliminar-paquete")}, "Se eliminara la receta del paquete. El SKU paquete se conserva."); return; }
            var editarGrupo = event.target.closest("[data-editar-grupo]"); if (editarGrupo) { prepararGrupo(encontrarGrupo(editarGrupo.getAttribute("data-editar-grupo"))); return; }
            var eliminarGrupo = event.target.closest("[data-eliminar-grupo]"); if (eliminarGrupo) { eliminar("/catalogoerp/eliminar_paquete_grupo", {id_grupo: eliminarGrupo.getAttribute("data-eliminar-grupo")}, "Se eliminara el grupo configurable y sus opciones."); return; }
            var nuevaOpcion = event.target.closest("[data-nueva-opcion]"); if (nuevaOpcion) { prepararOpcion(nuevaOpcion.getAttribute("data-nueva-opcion"), null); return; }
            var editarOpcion = event.target.closest("[data-editar-opcion]"); if (editarOpcion) { var opcion = encontrarOpcion(editarOpcion.getAttribute("data-editar-opcion")); prepararOpcion(opcion ? opcion.id_grupo : null, opcion); return; }
            var eliminarOpcion = event.target.closest("[data-eliminar-opcion]"); if (eliminarOpcion) { eliminar("/catalogoerp/eliminar_paquete_opcion", {id_opcion: eliminarOpcion.getAttribute("data-eliminar-opcion")}, "Se eliminara la opcion del grupo configurable."); }
        });
        document.addEventListener("change", function (event) { if (event.target.id === "paquete_opcion_grupo") { setValor(el("paquete_form_opcion"), "id_grupo", event.target.value); } });
    }

    document.addEventListener("DOMContentLoaded", function () { bind(); renderComponentes(); cargar(); });
})();