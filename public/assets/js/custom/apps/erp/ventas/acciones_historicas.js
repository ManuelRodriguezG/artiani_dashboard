"use strict";
(function () {
    var state = {items: [], current: null, selectedSku: null};

    /** IA: Codex GPT-6 | Fecha: 2026-10-31 | Solicitudes autenticadas con CSRF para la bandeja histórica. */
    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""} : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) { var el = document.createElement("div"); el.textContent = value == null ? "" : String(value); return el.innerHTML; }
    function money(value) { return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0)); }
    function notify(text, icon) { if (window.Swal) { return Swal.fire({text: text, icon: icon || "info", confirmButtonText: "Aceptar"}); } window.alert(text); return Promise.resolve(); }
    function queryParams() {
        var p = new URLSearchParams();
        p.set("estatus", document.getElementById("vha_estatus").value || "abiertas");
        p.set("limite", "100");
        var folio = document.getElementById("vha_folio").value.trim();
        if (folio) { p.set("folio", folio); }
        var notif = Number(window.VHA_NOTIFICACION_INICIAL || 0);
        var detail = Number(window.VHA_DETALLE_INICIAL || 0);
        if (notif) { p.set("id_notificacion", String(notif)); }
        else if (detail) { p.set("id_venta_detalle", String(detail)); }
        else if (window.VHA_FOLIO_INICIAL) { p.set("folio", window.VHA_FOLIO_INICIAL); }
        return p;
    }
    function loadList(selectNotification) {
        var body = document.getElementById("vha_lista");
        body.innerHTML = '<div class="text-center text-muted py-8">Consultando acciones históricas...</div>';
        request("/ventas/ventas_historicas_acciones_listar_erp?" + queryParams().toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudieron consultar las partidas"); }
            state.items = (response.depurar || {}).acciones || [];
            document.getElementById("vha_resumen").textContent = state.items.length + " partida(s)";
            renderList();
            var wanted = Number(selectNotification || window.VHA_NOTIFICACION_INICIAL || 0);
            var row = wanted ? state.items.find(function (item) { return Number(item.id_notificacion) === wanted; }) : state.items[0];
            if (row) { openItem(row.id_notificacion); }
            else { document.getElementById("vha_detalle").innerHTML = '<div class="text-center text-muted py-10">No hay partidas históricas con acciones pendientes para estos filtros.</div>'; }
        }).catch(function (error) {
            body.innerHTML = '<div class="alert alert-warning">' + escapeHtml(error.message || String(error)) + '</div>';
        });
    }
    function renderList() {
        var list = document.getElementById("vha_lista");
        if (!state.items.length) { list.innerHTML = '<div class="text-center text-muted py-8">Sin partidas por revisar</div>'; return; }
        list.innerHTML = state.items.map(function (item) {
            var active = state.current && Number(state.current.id_notificacion) === Number(item.id_notificacion) ? " active" : "";
            return '<div class="vha-row border rounded p-3 mb-2' + active + '" data-vha-notificacion="' + Number(item.id_notificacion) + '">' +
                '<div class="d-flex justify-content-between gap-2"><div class="fw-bold">' + escapeHtml(item.folio_venta || "Venta histórica") + '</div><span class="badge badge-light-warning">' + escapeHtml(item.estatus_notificacion || "pendiente") + '</span></div>' +
                '<div class="mt-2">' + escapeHtml(item.descripcion || "") + '</div>' +
                '<div class="text-muted fs-8 mt-1">Partida ' + escapeHtml(item.renglon || "-") + ' · ' + money(item.total) + '</div></div>';
        }).join("");
    }
    function openItem(id) {
        var item = state.items.find(function (candidate) { return Number(candidate.id_notificacion) === Number(id); });
        if (!item) { return; }
        state.current = item; state.selectedSku = null; renderList(); renderDetail();
    }
    function renderDetail() {
        var item = state.current;
        var root = document.getElementById("vha_detalle");
        if (!item) { root.innerHTML = '<div class="text-center text-muted py-10">Selecciona una partida pendiente</div>'; return; }
        var open = ["pendiente", "en_revision", "bloqueada"].indexOf(String(item.estatus_notificacion)) >= 0;
        root.innerHTML = '<div class="row g-4">' +
            '<div class="col-12"><div class="border rounded p-4"><div class="d-flex justify-content-between flex-wrap gap-2"><div><div class="fw-bold fs-5">' + escapeHtml(item.descripcion || "Producto histórico") + '</div><div class="text-muted fs-7">' + escapeHtml(item.folio_venta) + ' · ' + escapeHtml(item.fecha_venta_real || "") + '</div></div><span class="badge badge-light-primary">' + escapeHtml(item.estatus_notificacion) + '</span></div>' +
            '<div class="row g-3 mt-1 fs-7">' + field("Cantidad vendida", String(item.cantidad_venta || "0") + " " + String(item.unidad_venta || "")) + field("Cantidad base actual", String(item.cantidad_base || "0") + " " + String(item.unidad_base || "")) + field("Precio por unidad vendida", money(item.precio_unitario)) + field("Total de partida", money(item.total)) + field("Almacén", String(item.id_almacen || "-")) + field("SKU registrado", item.sku || "Venta rápida sin SKU") + '</div>' +
            '<div class="row g-3 mt-3"><div class="col-md-6"><label class="form-label">Cantidad equivalente en unidad base del SKU</label><input class="form-control" id="vha_cantidad_base" inputmode="decimal" value="' + escapeHtml(item.cantidad_base || "") + '"></div><div class="col-md-6"><label class="form-label">Unidad base</label><input class="form-control" id="vha_unidad_base" value="' + escapeHtml(item.unidad_base || "") + '"></div></div>' +
            '<div class="alert alert-light-info mt-3 mb-0 py-2 fs-8">Si la porción vendida y la unidad base del SKU son distintas, captura la equivalencia (por ejemplo, 3 porciones de 100 g = 0.3 kg). Esto actualiza solo la cantidad base histórica; conserva cantidad vendida, precio y total, y no afecta inventario ni kardex.</div></div></div>' +
            '<div class="col-xl-7"><div class="border rounded p-4 h-100"><div class="fw-bold fs-5 mb-1">Vincular a un SKU existente</div><div class="text-muted fs-7 mb-3">Busca por nombre o código y revisa el candidato antes de guardar.</div>' +
            '<div class="input-group mb-3"><input class="form-control" id="vha_sku_busqueda" placeholder="SKU, código o nombre"><button class="btn btn-light-primary" id="vha_sku_buscar" type="button"><i class="bi bi-search"></i> Buscar</button></div><div id="vha_sku_resultados"></div>' +
            '<div class="mt-3"><label class="form-label">SKU seleccionado</label><input class="form-control" id="vha_sku_id" readonly placeholder="Selecciona un resultado" value=""></div>' +
            '<div class="mt-3"><label class="form-label">Motivo</label><textarea class="form-control" id="vha_motivo" rows="2" placeholder="Ej. Se identificó el producto correcto en Catálogo"></textarea></div>' +
            '<div class="d-flex flex-wrap gap-2 mt-3">' + (window.VHA_PUEDE_CLASIFICAR ? '<button class="btn btn-light-primary" id="vha_prevalidar" type="button"' + (open ? "" : " disabled") + '>Prevalidar clasificación</button><button class="btn btn-primary" id="vha_aplicar" type="button"' + (open ? "" : " disabled") + '>Clasificar partida</button>' : '<span class="text-muted fs-8">Consulta de solo lectura; solicita a un usuario autorizado que clasifique la partida.</span>') + '</div><div id="vha_resultado" class="mt-3"></div></div></div>' +
            '<div class="col-xl-5"><div class="border rounded p-4 h-100"><div class="fw-bold fs-5 mb-1">No existe en Catálogo</div><div class="text-muted fs-7 mb-3">Envía una incidencia a Catálogo para que decidan si corresponde crear un SKU. Después vuelve aquí para vincularlo a la venta histórica.</div><button class="btn btn-light-warning w-100" id="vha_solicitar_catalogo" type="button"' + (open ? "" : " disabled") + '>Solicitar revisión a Catálogo</button><div id="vha_catalogo_resultado" class="mt-3"></div></div></div>' +
            (window.VHA_PUEDE_CLASIFICAR ? '<div class="col-12"><button class="btn btn-light-secondary" id="vha_mantener_rapida" type="button"' + (open ? "" : " disabled") + '>Mantener como venta rápida y cerrar esta acción</button><span class="text-muted fs-8 ms-3">Úsalo si no corresponde crear ni vincular un producto de Catálogo.</span></div>' : '') +
            '</div>';
    }
    function field(label, value) { return '<div class="col-md-4"><div class="text-muted fs-8 text-uppercase">' + escapeHtml(label) + '</div><div class="fw-semibold">' + escapeHtml(value == null || value === "" ? "-" : value) + '</div></div>'; }
    /** IA: Codex GPT-6 | Fecha: 2026-11-01 | Adjunta conversión base propuesta y los valores originales para revalidación transaccional. */
    function detailPayload() {
        return {
            id_notificacion: state.current.id_notificacion,
            id_venta_detalle: state.current.id_venta_detalle,
            folio: state.current.folio_venta,
            cantidad_base_original: state.current.cantidad_base,
            unidad_base_original: state.current.unidad_base,
            cantidad_base_nueva: document.getElementById("vha_cantidad_base") ? document.getElementById("vha_cantidad_base").value.trim() : state.current.cantidad_base,
            unidad_base_nueva: document.getElementById("vha_unidad_base") ? document.getElementById("vha_unidad_base").value.trim() : state.current.unidad_base
        };
    }
    function searchSku() {
        var q = document.getElementById("vha_sku_busqueda").value.trim();
        if (q.length < 2) { notify("Escribe al menos dos caracteres para buscar.", "warning"); return; }
        request("/ventas/pos_buscar_skus_erp?q=" + encodeURIComponent(q) + "&limite=12").then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudieron buscar SKUs"); }
            var items = response.depurar || [];
            document.getElementById("vha_sku_resultados").innerHTML = items.length ? items.map(function (sku) {
                var unidadBase = sku.unidad_base_abreviatura || sku.unidad_base_codigo || sku.unidad_base_nombre || "";
                return '<button class="btn btn-light text-start w-100 mb-2" type="button" data-vha-sku="' + Number(sku.id_sku || 0) + '" data-vha-unidad-base="' + escapeHtml(unidadBase) + '"><div class="fw-bold">' + escapeHtml(sku.sku || "-") + ' · ' + escapeHtml(sku.nombre_sku || sku.producto || "") + '</div><div class="text-muted fs-8">ID ' + Number(sku.id_sku || 0) + ' · Unidad base: ' + escapeHtml(unidadBase || "sin unidad") + ' · ' + money(sku.precio || 0) + '</div></button>';
            }).join("") : '<div class="alert alert-light-warning py-2">No se encontraron candidatos.</div>';
        }).catch(function (error) { notify(error.message || String(error), "error"); });
    }
    function prevalidar() {
        if (!state.selectedSku) { notify("Selecciona un SKU antes de prevalidar.", "warning"); return; }
        request("/ventas/ventas_historicas_clasificar_dryrun_erp", Object.assign(detailPayload(), {id_sku: state.selectedSku})).then(function (response) {
            var data = response.depurar || {};
            var blockers = data.bloqueos || [];
            var plan = data.plan || {};
            var base = plan.actualizaria_cantidad_base || {};
            document.getElementById("vha_resultado").innerHTML = '<div class="alert alert-' + (blockers.length ? "warning" : "success") + '"><div class="fw-bold">' + escapeHtml(response.mensaje || "Prevalidación") + '</div>' + (blockers.length ? '<ul class="mb-0 mt-2">' + blockers.map(function (b) { return '<li>' + escapeHtml(b) + '</li>'; }).join("") + '</ul>' : '<div class="mt-2">SKU: ' + escapeHtml((data.sku || {}).sku || "") + ' · Cantidad base: ' + escapeHtml(base.anterior || "0") + ' ' + escapeHtml(base.unidad_anterior || "") + ' → <strong>' + escapeHtml(base.nueva || "0") + ' ' + escapeHtml(base.unidad_nueva || "") + '</strong>. Se conservarán la cantidad vendida, el precio, el total, la descripción y el almacén.</div>') + '</div>';
        }).catch(function (error) { notify(error.message || String(error), "error"); });
    }
    function applyClassification() {
        if (!state.selectedSku) { notify("Selecciona un SKU antes de clasificar.", "warning"); return; }
        var motivo = document.getElementById("vha_motivo").value.trim();
        if (!motivo) { notify("Escribe el motivo de clasificación para la auditoría.", "warning"); return; }
        var cantidadBase = document.getElementById("vha_cantidad_base").value.trim();
        var unidadBase = document.getElementById("vha_unidad_base").value.trim();
        if (!cantidadBase || Number(cantidadBase) <= 0 || !unidadBase) { notify("Indica una cantidad y unidad base válidas antes de clasificar.", "warning"); return; }
        var descripcionCambio = "Se vinculará el SKU seleccionado y la cantidad base quedará en " + cantidadBase + " " + unidadBase + ". La cantidad vendida, precio y total históricos se conservarán; no hay movimiento de inventario.";
        var confirm = window.Swal ? Swal.fire({title: "Clasificar partida histórica", text: descripcionCambio, icon: "warning", showCancelButton: true, confirmButtonText: "Confirmar clasificación", cancelButtonText: "Cancelar", input: "text", inputLabel: 'Escribe CLASIFICAR VENTA HISTORICA', inputValidator: function (value) { return value === "CLASIFICAR VENTA HISTORICA" ? undefined : "La confirmación debe coincidir exactamente."; }}).then(function (r) { return r.isConfirmed && r.value === "CLASIFICAR VENTA HISTORICA"; }) : Promise.resolve(window.confirm("¿" + descripcionCambio + "?"));
        confirm.then(function (ok) {
            if (!ok) { return; }
            var datos = Object.assign(detailPayload(), {id_sku: state.selectedSku, motivo: motivo, token: "VENTAS_HISTORICAS_CLASIFICAR_REAL", confirmacion: "CLASIFICAR VENTA HISTORICA"});
            request("/ventas/ventas_historicas_clasificar_aplicar_erp", datos).then(function (response) {
                if (response.error) { throw new Error(response.mensaje || "No se pudo clasificar"); }
                notify(response.mensaje || "Partida clasificada", "success").then(function () { loadList(); });
            }).catch(function (error) { notify(error.message || String(error), "error"); });
        });
    }
    function requestCatalog() {
        if (!state.current) { return; }
        var motivo = document.getElementById("vha_motivo").value.trim();
        if (!motivo) { notify("Escribe el motivo de la solicitud para dejar trazabilidad.", "warning"); return; }
        var confirm = window.Swal ? Swal.fire({title: "Enviar a revisión de Catálogo", text: "Se creará una incidencia para revisar el producto. La venta histórica permanecerá intacta.", icon: "question", showCancelButton: true, confirmButtonText: "Enviar solicitud", cancelButtonText: "Cancelar"}).then(function (r) { return r.isConfirmed; }) : Promise.resolve(window.confirm("¿Enviar esta partida histórica a revisión de Catálogo?"));
        confirm.then(function (ok) {
            if (!ok) { return; }
        var datos = Object.assign(detailPayload(), {motivo: motivo});
        request("/ventas/ventas_historicas_solicitar_catalogo_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo enviar a Catálogo"); }
            var data = response.depurar || {};
            document.getElementById("vha_catalogo_resultado").innerHTML = '<div class="alert alert-success"><div class="fw-bold">' + escapeHtml(response.mensaje) + '</div><div class="fs-8 mt-2">Incidencia #' + Number(data.id_incidencia_calidad || 0) + '</div><a class="btn btn-sm btn-light-primary mt-2" href="' + escapeHtml(data.url_catalogo || "/catalogoerp") + '">Abrir incidencia en Catálogo</a></div>';
        }).catch(function (error) { notify(error.message || String(error), "error"); });
        });
    }
    function maintainQuickSale() {
        if (!state.current) { return; }
        var motivo = document.getElementById("vha_motivo").value.trim();
        if (!motivo) { notify("Escribe por qué esta partida debe permanecer como venta rápida.", "warning"); return; }
        var confirm = window.Swal ? Swal.fire({title: "Mantener como venta rápida", text: "Se cerrará la tarea de clasificación. La partida y sus importes permanecerán intactos.", icon: "warning", showCancelButton: true, confirmButtonText: "Cerrar acción", cancelButtonText: "Volver", input: "text", inputLabel: 'Escribe MANTENER VENTA RAPIDA', inputValidator: function (value) { return value === "MANTENER VENTA RAPIDA" ? undefined : "La confirmación debe coincidir exactamente."; }}).then(function (r) { return r.isConfirmed && r.value === "MANTENER VENTA RAPIDA"; }) : Promise.resolve(window.confirm("¿Cerrar la acción y conservar la venta rápida?"));
        confirm.then(function (ok) {
            if (!ok) { return; }
            request("/ventas/ventas_historicas_mantener_rapida_erp", Object.assign(detailPayload(), {motivo: motivo, token: "VENTAS_HISTORICAS_MANTENER_RAPIDA", confirmacion: "MANTENER VENTA RAPIDA"})).then(function (response) {
                if (response.error) { throw new Error(response.mensaje || "No se pudo cerrar la acción"); }
                notify(response.mensaje || "Acción cerrada", "success").then(function () { loadList(); });
            }).catch(function (error) { notify(error.message || String(error), "error"); });
        });
    }
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("vha_consultar").addEventListener("click", function () { window.VHA_NOTIFICACION_INICIAL = 0; window.VHA_DETALLE_INICIAL = 0; window.VHA_FOLIO_INICIAL = ""; loadList(); });
        document.getElementById("vha_recargar").addEventListener("click", function () { loadList(state.current && state.current.id_notificacion); });
        document.getElementById("vha_lista").addEventListener("click", function (event) { var row = event.target.closest("[data-vha-notificacion]"); if (row) { openItem(row.getAttribute("data-vha-notificacion")); } });
        document.getElementById("vha_detalle").addEventListener("click", function (event) {
            if (event.target.closest("#vha_sku_buscar")) { searchSku(); return; }
            if (event.target.closest("#vha_prevalidar")) { prevalidar(); return; }
            if (event.target.closest("#vha_aplicar")) { applyClassification(); return; }
            if (event.target.closest("#vha_solicitar_catalogo")) { requestCatalog(); return; }
            if (event.target.closest("#vha_mantener_rapida")) { maintainQuickSale(); return; }
            var button = event.target.closest("[data-vha-sku]");
            if (button) {
                state.selectedSku = Number(button.getAttribute("data-vha-sku"));
                document.getElementById("vha_sku_id").value = String(state.selectedSku);
                var unidadBase = button.getAttribute("data-vha-unidad-base") || "";
                var unidadAnterior = String(state.current.unidad_base || "").toLowerCase();
                var unidadNueva = unidadBase.toLowerCase();
                if (unidadBase) { document.getElementById("vha_unidad_base").value = unidadBase; }
                if (unidadNueva && unidadAnterior && unidadNueva !== unidadAnterior) { document.getElementById("vha_cantidad_base").value = ""; }
            }
        });
        document.getElementById("vha_folio").addEventListener("keydown", function (event) { if (event.key === "Enter") { document.getElementById("vha_consultar").click(); } });
        if (window.VHA_FOLIO_INICIAL) { document.getElementById("vha_folio").value = window.VHA_FOLIO_INICIAL; }
        loadList(window.VHA_NOTIFICACION_INICIAL || 0);
    });
})();
