"use strict";
(function () {
    function request(url) { return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); }); }
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString()
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function filtros() {
        return new URLSearchParams({
            q: document.getElementById("inventario_filtro_buscar").value.trim(),
            id_almacen: document.getElementById("inventario_filtro_almacen").value,
            estado_fisico: document.getElementById("inventario_filtro_estado_fisico").value,
            incluir_agotadas: document.getElementById("inventario_filtro_agotadas").checked ? "1" : "0"
        }).toString();
    }
    function filtrosDiagnostico() {
        return new URLSearchParams({
            id_almacen: document.getElementById("inventario_filtro_almacen").value
        }).toString();
    }
    function cargarCatalogos() {
        return request("/inventario/catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            document.getElementById("inventario_filtro_almacen").innerHTML = "<option value=\"\">Todos los almacenes</option>" + (response.depurar.almacenes || []).map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
        });
    }
    function cargar() {
        Promise.all([
            request("/inventario/existencias_erp?" + filtros()),
            request("/inventario/movimientos_erp?" + filtros()),
            request("/inventario/unidades_erp?" + filtros()),
            request("/inventario/diagnostico_erp?" + filtrosDiagnostico()),
            request("/inventario/valuacion_erp?" + filtros()),
            request("/inventario/pos_pendientes_inventario_erp?" + filtrosPendientesPos())
        ]).then(function (responses) {
            responses.forEach(function (response) { if (response.error) { throw new Error(response.mensaje); } });
            renderExistencias(responses[0].depurar || []);
            renderMovimientos(responses[1].depurar || []);
            renderUnidades(responses[2].depurar || []);
            renderDiagnostico(responses[3].depurar || {});
            renderValuacion(responses[4].depurar || {});
            renderPendientesPos(responses[5].depurar || {});
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    function filtrosPendientesPos() {
        return new URLSearchParams({
            q: document.getElementById("inventario_filtro_buscar").value.trim(),
            id_almacen: document.getElementById("inventario_filtro_almacen").value,
            estatus: "todos"
        }).toString();
    }
    function dinero(value) {
        return "$" + Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function claseDiagnostico(severidad) {
        if (severidad === "danger") { return {alerta: "alert-danger", badge: "badge-light-danger", icono: "bi-exclamation-octagon"}; }
        if (severidad === "warning") { return {alerta: "alert-warning", badge: "badge-light-warning", icono: "bi-exclamation-triangle"}; }
        return {alerta: "alert-info", badge: "badge-light-info", icono: "bi-info-circle"};
    }
    function textoItemDiagnostico(item) {
        if (item.estado_etiqueta) {
            return escapeHtml(item.estado_etiqueta) + ": " + Number(item.total || 0) + " (" + escapeHtml(item.primer_codigo || "-") + " - " + escapeHtml(item.ultimo_codigo || "-") + ")";
        }
        if (item.folio && item.reserva_pendiente !== undefined) {
            return escapeHtml(item.folio) + " · " + escapeHtml(item.codigo_existencia || "-") + " · pendiente " + Number(item.reserva_pendiente || 0).toFixed(4) + " · " + escapeHtml(item.estatus || "");
        }
        var base = escapeHtml(item.codigo_existencia || item.sku || "-") + " · " + escapeHtml(item.sku || "") + " · " + escapeHtml(item.almacen || "-");
        if (item.fecha_caducidad) { base += " · cad. " + escapeHtml(item.fecha_caducidad); }
        if (item.cantidad !== undefined) { base += " · saldo " + Number(item.cantidad || 0).toFixed(2); }
        if (item.cantidad_apartada !== undefined) { base += " · apartada " + Number(item.cantidad_apartada || 0).toFixed(4); }
        if (item.reserva_pendiente !== undefined) { base += " · reserva " + Number(item.reserva_pendiente || 0).toFixed(4); }
        if (item.unidades_disponibles !== undefined) { base += " · unidades " + Number(item.unidades_disponibles || 0).toFixed(0); }
        return base;
    }
    function renderDiagnostico(data) {
        var contenedor = document.getElementById("inventario_diagnostico");
        var hallazgos = data.hallazgos || [];
        if (!hallazgos.length) {
            contenedor.innerHTML = "<div class=\"alert alert-success d-flex align-items-center py-3 mb-0\"><i class=\"bi bi-check-circle fs-2 me-3\"></i><div><div class=\"fw-bold\">Diagnostico operativo sin hallazgos</div><div class=\"fs-8\">No se detectaron negativos, descuadres, caducidades criticas, pendientes de etiquetas ni reservas inconsistentes.</div></div></div>";
            return;
        }
        contenedor.innerHTML = "<div class=\"d-flex flex-column gap-3\">" + hallazgos.map(function (hallazgo) {
            var clase = claseDiagnostico(hallazgo.severidad);
            var items = (hallazgo.items || []).slice(0, 4).map(function (item) {
                return "<div class=\"text-muted fs-8\">" + textoItemDiagnostico(item) + "</div>";
            }).join("");
            var mas = (hallazgo.items || []).length > 4 ? "<div class=\"text-muted fs-8\">+" + ((hallazgo.items || []).length - 4) + " registros mas</div>" : "";
            return "<div class=\"alert " + clase.alerta + " py-3 mb-0\"><div class=\"d-flex align-items-start\"><i class=\"bi " + clase.icono + " fs-2 me-3\"></i><div class=\"flex-grow-1\"><div class=\"d-flex flex-wrap gap-2 align-items-center\"><span class=\"fw-bold\">" + escapeHtml(hallazgo.titulo) + "</span><span class=\"badge " + clase.badge + "\">" + Number(hallazgo.total || 0) + "</span><span class=\"text-muted fs-8\">" + escapeHtml(hallazgo.id || "") + "</span></div>" + items + mas + "</div></div></div>";
        }).join("") + "</div>";
    }
    function botonTrazabilidad(clave) {
        if (!clave) { return ""; }
        return "<button class=\"btn btn-sm btn-light-info\" type=\"button\" data-trazabilidad=\"" + escapeHtml(clave) + "\"><i class=\"bi bi-diagram-3\"></i> Trazabilidad</button>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: presenta estado fisico y contenido base de unidades trazables sin confundir unidad abierta con unidad cerrada vendible.
     * Impacto: UI de Inventario/Existencias y modal de trazabilidad.
     */
    function estadoFisico(estado) {
        if (estado === "cerrada") { return {clase: "badge-light-success", texto: "Cerrada"}; }
        if (estado === "abierta") { return {clase: "badge-light-warning", texto: "Abierta"}; }
        if (estado === "consumida") { return {clase: "badge-light-secondary", texto: "Consumida"}; }
        if (estado === "agotada") { return {clase: "badge-light-secondary", texto: "Agotada"}; }
        if (estado === "vendida") { return {clase: "badge-light-primary", texto: "Vendida"}; }
        if (estado === "cancelada") { return {clase: "badge-light-danger", texto: "Cancelada"}; }
        return {clase: "badge-light", texto: estado || "Sin estado"};
    }
    function formatoContenidoUnidad(item) {
        var unidad = item.unidad_base || item.unidad_base_trazable || "";
        var disponible = Number(item.cantidad_base_disponible || item.contenido_base_disponible || 0);
        var original = Number(item.cantidad_base_original || item.contenido_base_original || 0);
        if (!original && !disponible) { return "Sin contenido base"; }
        return disponible.toFixed(4) + (unidad ? " " + escapeHtml(unidad) : "") + " / " + original.toFixed(4);
    }
    function resumenUnidadesExistencia(item) {
        var total = Number(item.unidades_total || 0);
        if (!total) {
            return "<div class=\"text-muted fs-8\">Sin unidades fisicas</div>";
        }
        var partes = [
            "<span class=\"badge badge-light-success me-1\">Cerradas " + Number(item.unidades_cerradas || 0).toFixed(0) + "</span>",
            "<span class=\"badge badge-light-warning me-1\">Abiertas " + Number(item.unidades_abiertas || 0).toFixed(0) + "</span>"
        ];
        var consumidas = Number(item.unidades_consumidas || 0);
        if (consumidas) { partes.push("<span class=\"badge badge-light-secondary me-1\">Consumidas " + consumidas.toFixed(0) + "</span>"); }
        var pendientes = Number(item.etiquetas_pendientes || 0) + Number(item.etiquetas_impresas || 0);
        if (pendientes) { partes.push("<span class=\"badge badge-light-info me-1\">Etiquetas pendientes " + pendientes.toFixed(0) + "</span>"); }
        var diferencia = Number(item.diferencia_contenido_unidades || 0);
        var claseDiferencia = Math.abs(diferencia) > 0.0001 ? "text-danger" : "text-muted";
        return "<div class=\"mb-1\">" + partes.join("") + "</div>" +
            "<div class=\"fs-8 " + claseDiferencia + "\">Trazable " + Number(item.contenido_base_disponible || 0).toFixed(4) + " " + escapeHtml(item.unidad_base_trazable || "") + " · dif. " + diferencia.toFixed(4) + "</div>";
    }
    function resumenVacio(texto) {
        return "<div class=\"text-center text-muted py-5\">" + escapeHtml(texto) + "</div>";
    }
    function abrirTrazabilidad(clave) {
        var titulo = document.getElementById("inventario_trazabilidad_titulo");
        var body = document.getElementById("inventario_trazabilidad_body");
        titulo.textContent = "Trazabilidad: " + clave;
        body.innerHTML = "<div class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando trazabilidad</div>";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("inventario_trazabilidad_modal")).show();
        request("/inventario/trazabilidad_erp?" + new URLSearchParams({q: clave}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            body.innerHTML = renderTrazabilidad(response.depurar || {});
        }).catch(function (error) {
            body.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function renderTrazabilidad(data) {
        var existencias = data.existencias || [];
        var movimientos = data.movimientos || [];
        var unidades = data.unidades || [];
        return "<div class=\"d-flex flex-wrap gap-2 mb-5\">" +
            "<span class=\"badge badge-light-primary fs-7\">Existencias " + existencias.length + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Movimientos " + movimientos.length + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Unidades " + unidades.length + "</span>" +
            "</div>" +
            "<h4 class=\"fs-6 fw-bold mb-3\">Existencias</h4>" + renderTrazabilidadExistencias(existencias) +
            "<h4 class=\"fs-6 fw-bold mt-6 mb-3\">Kardex</h4>" + renderTrazabilidadMovimientos(movimientos) +
            "<h4 class=\"fs-6 fw-bold mt-6 mb-3\">Unidades</h4>" + renderTrazabilidadUnidades(unidades);
    }
    function renderTrazabilidadExistencias(items) {
        if (!items.length) { return resumenVacio("Sin existencias relacionadas"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Existencia</th><th>SKU</th><th>Almacen</th><th>Lote</th><th>Ubicacion</th><th>Saldo</th></tr></thead><tbody>" + items.map(function (item) {
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.codigo_existencia || "") + "</td><td>" + escapeHtml(item.sku || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.producto || item.nombre_sku || "") + "</div></td><td>" + escapeHtml(item.almacen || "-") + "</td><td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "") + "</div></td><td>" + escapeHtml(item.ubicacion || "-") + "</td><td class=\"fw-bold\">" + Number(item.cantidad || 0).toFixed(2) + "<div class=\"text-success fs-8\">Disp. " + Number(item.cantidad_disponible || 0).toFixed(2) + "</div></td></tr>";
        }).join("") + "</tbody></table></div>";
    }
    function renderTrazabilidadMovimientos(items) {
        if (!items.length) { return resumenVacio("Sin movimientos relacionados"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Fecha</th><th>Tipo</th><th>SKU</th><th>Cantidad</th><th>Referencia</th><th>Saldo</th></tr></thead><tbody>" + items.map(function (item) {
            var clase = item.tipo_movimiento === "entrada" ? "badge-light-success" : "badge-light-danger";
            var signo = item.tipo_movimiento === "entrada" ? "+" : "-";
            var anterior = item.existencia_anterior === null || item.existencia_anterior === undefined ? "-" : Number(item.existencia_anterior || 0).toFixed(2);
            var nueva = item.existencia_nueva === null || item.existencia_nueva === undefined ? "-" : Number(item.existencia_nueva || 0).toFixed(2);
            return "<tr><td>" + escapeHtml(item.fecha_registro || "") + "</td><td><span class=\"badge " + clase + "\">" + escapeHtml(item.tipo_movimiento || "") + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_tipo || "") + "</div></td><td>" + escapeHtml(item.sku || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + "</div></td><td class=\"fw-bold\">" + signo + Number(item.cantidad || 0).toFixed(2) + "</td><td>" + escapeHtml(item.referencia || "-") + "</td><td>" + anterior + " / " + nueva + "</td></tr>";
        }).join("") + "</tbody></table></div>";
    }
    function renderTrazabilidadUnidades(items) {
        if (!items.length) { return resumenVacio("Sin unidades relacionadas"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Codigo</th><th>SKU</th><th>Almacen</th><th>Lote</th><th>Origen</th><th>Estado</th></tr></thead><tbody>" + items.map(function (item) {
            var codigo = item.codigo_etiqueta_interna || item.serie_fabricante || item.codigo_unico || "-";
            var estado = estadoEtiqueta(item.estado_etiqueta);
            var fisico = estadoFisico(item.estado_fisico);
            return "<tr><td class=\"fw-bold\">" + escapeHtml(codigo) + "<div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_identidad || "") + "</div></td><td>" + escapeHtml(item.sku || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td><td>" + escapeHtml(item.almacen || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div></td><td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "") + "</div><div class=\"text-muted fs-8\">" + formatoContenidoUnidad(item) + "</div></td><td>" + escapeHtml(item.folio_recepcion || item.origen_tipo || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.folio_orden_compra || "") + "</div></td><td><span class=\"badge " + fisico.clase + " me-1\">" + fisico.texto + "</span><span class=\"badge " + estado.clase + "\">" + estado.texto + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.estatus || "") + "</div></td></tr>";
        }).join("") + "</tbody></table></div>";
    }
    function renderExistencias(items) {
        var total = items.reduce(function (suma, item) { return suma + Number(item.cantidad || 0); }, 0);
        var disponible = items.reduce(function (suma, item) { return suma + Number(item.cantidad_disponible || 0); }, 0);
        var agotadas = items.filter(function (item) {
            return Number(item.cantidad || 0) === 0 && Number(item.cantidad_disponible || 0) === 0 && Number(item.cantidad_apartada || 0) === 0;
        }).length;
        var abiertas = items.reduce(function (suma, item) { return suma + Number(item.unidades_abiertas || 0); }, 0);
        var cerradas = items.reduce(function (suma, item) { return suma + Number(item.unidades_cerradas || 0); }, 0);
        document.getElementById("inventario_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Registros " + items.length + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Existencia " + total.toFixed(2) + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Disponible " + disponible.toFixed(2) + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Unid. cerradas " + cerradas.toFixed(0) + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Unid. abiertas " + abiertas.toFixed(0) + "</span>" +
            (agotadas ? "<span class=\"badge badge-light-secondary fs-7\">Agotadas " + agotadas + "</span>" : "");
        document.getElementById("inventario_existencias").innerHTML = items.map(function (item) {
            var agotada = Number(item.cantidad || 0) === 0 && Number(item.cantidad_disponible || 0) === 0 && Number(item.cantidad_apartada || 0) === 0;
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre_sku || item.producto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td><td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "") + "</div></td>" +
                "<td>" + escapeHtml(item.ubicacion || "-") + "</td><td class=\"fw-bold\">" + Number(item.cantidad || 0).toFixed(2) + "</td>" +
                "<td class=\"text-success fw-bold\">" + Number(item.cantidad_disponible || 0).toFixed(2) + (agotada ? "<div class=\"badge badge-light-secondary mt-1\">Agotada</div>" : "") + "<div class=\"mt-2\">" + resumenUnidadesExistencia(item) + "</div></td><td>$" + Number(item.costo_promedio || 0).toFixed(2) + "</td>" +
                "<td class=\"text-end\">" + botonTrazabilidad(item.codigo_existencia || item.sku) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin existencias registradas</td></tr>";
    }
    function renderValuacion(data) {
        var resumen = data.resumen || {};
        var items = data.items || [];
        document.getElementById("inventario_valuacion_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">SKUs " + Number(resumen.skus || 0) + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Valor " + dinero(resumen.valor_total || 0) + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Cantidad " + Number(resumen.cantidad_total || 0).toFixed(2) + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Apartada " + Number(resumen.apartada_total || 0).toFixed(2) + "</span>";
        document.getElementById("inventario_valuacion").innerHTML = items.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + Number(item.existencias || 0).toFixed(0) + "</td>" +
                "<td class=\"fw-bold\">" + Number(item.cantidad_total || 0).toFixed(2) + "</td>" +
                "<td class=\"text-success fw-bold\">" + Number(item.disponible_total || 0).toFixed(2) + "</td>" +
                "<td>" + Number(item.apartada_total || 0).toFixed(2) + "</td>" +
                "<td>" + dinero(item.costo_promedio_estimado || 0) + "</td>" +
                "<td class=\"fw-bold\">" + dinero(item.valor_total || 0) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin inventario valuado</td></tr>";
    }
    function badgePendientePos(estatus) {
        if (estatus === "resuelto") { return {clase: "badge-light-success", texto: "Resuelto"}; }
        if (estatus === "en_revision") { return {clase: "badge-light-warning", texto: "En revision"}; }
        if (estatus === "cancelado") { return {clase: "badge-light-secondary", texto: "Cancelado"}; }
        return {clase: "badge-light-danger", texto: "Pendiente"};
    }
    function renderPendientesPos(data) {
        var items = data.pendientes || [];
        var abiertos = items.filter(function (item) { return item.estatus === "pendiente_revision" || item.estatus === "en_revision"; }).length;
        var resueltos = items.filter(function (item) { return item.estatus === "resuelto"; }).length;
        var cantidadPendiente = items.reduce(function (suma, item) { return suma + Number(item.cantidad_pendiente || 0); }, 0);
        document.getElementById("inventario_pendientes_pos_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Registros " + items.length + "</span>" +
            "<span class=\"badge badge-light-danger fs-7\">Abiertos " + abiertos + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Resueltos " + resueltos + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Pendiente " + cantidadPendiente.toFixed(2) + "</span>";
        document.getElementById("inventario_pendientes_pos").innerHTML = items.map(function (item) {
            var estado = badgePendientePos(item.estatus);
            var folio = item.folio || "";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(folio) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.folio_venta || "-") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td><div>Vendida <span class=\"fw-bold\">" + Number(item.cantidad_vendida || 0).toFixed(2) + "</span></div><div class=\"text-muted fs-8\">Pendiente " + Number(item.cantidad_pendiente || 0).toFixed(2) + " " + escapeHtml(item.unidad_base || "") + "</div><div class=\"text-success fs-8\">Disp. actual " + Number(item.disponible_actual || 0).toFixed(2) + "</div></td>" +
                "<td><span class=\"badge " + estado.clase + "\">" + estado.texto + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.prioridad || "") + "</div></td>" +
                "<td><div class=\"fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_resolucion || "") + "</div></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-info\" type=\"button\" data-pendiente-pos=\"" + escapeHtml(folio) + "\"><i class=\"bi bi-folder2-open\"></i> Expediente</button></td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\"><div class=\"fw-semibold\">Sin pendientes POS</div><div class=\"fs-8 mt-1\">Las ventas autorizadas con inventario pendiente apareceran aqui para seguimiento y mini inventario.</div></td></tr>";
    }
    function abrirPendientePos(folio) {
        var titulo = document.getElementById("inventario_pendiente_pos_titulo");
        var body = document.getElementById("inventario_pendiente_pos_body");
        titulo.textContent = "Pendiente POS: " + folio;
        body.innerHTML = "<div class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando expediente</div>";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("inventario_pendiente_pos_modal")).show();
        request("/inventario/pos_pendiente_inventario_consultar_erp?" + new URLSearchParams({folio: folio}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            body.innerHTML = renderPendientePosDetalle(response.depurar || {});
        }).catch(function (error) {
            body.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function renderPendientePosDetalle(data) {
        var pendiente = data.pendiente || {};
        if (!pendiente.id_inventario_pendiente) {
            return "<div class=\"alert alert-warning mb-0\">Pendiente no encontrado.</div>";
        }
        var estado = badgePendientePos(pendiente.estatus);
        var existencias = data.existencias_actuales || [];
        var eventos = data.eventos || [];
        var existenciasHtml = existencias.length ? existencias.map(function (item) {
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.codigo_existencia || "") + "</td><td>" + escapeHtml(item.lote || "-") + "</td><td>" + escapeHtml(item.ubicacion || "-") + "</td><td>" + Number(item.cantidad || 0).toFixed(2) + "</td><td class=\"text-success fw-bold\">" + Number(item.cantidad_disponible || 0).toFixed(2) + "</td><td>" + escapeHtml(item.ultimo_movimiento_id || "-") + "</td></tr>";
        }).join("") : "<tr><td colspan=\"6\" class=\"text-center text-muted py-5\">Sin existencias actuales relacionadas</td></tr>";
        var eventosHtml = eventos.length ? eventos.map(function (item) {
            return "<tr><td>" + escapeHtml(item.fecha_registro || "") + "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.tipo_evento || "") + "</span></td><td>" + escapeHtml(item.estatus_anterior || "-") + " / " + escapeHtml(item.estatus_nuevo || "-") + "</td><td>" + Number(item.cantidad || 0).toFixed(2) + "</td><td>" + escapeHtml(item.referencia || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.observaciones || "") + "</div></td></tr>";
        }).join("") : "<tr><td colspan=\"5\" class=\"text-center text-muted py-5\">Sin eventos registrados</td></tr>";
        var formularioDryRun = "";
        if (pendiente.estatus === "pendiente_revision" || pendiente.estatus === "en_revision") {
            formularioDryRun = "<div class=\"border rounded p-4 mb-6\"><div class=\"fw-bold mb-3\">Simular resolucion</div>" +
                "<form data-pendiente-pos-dryrun=\"" + escapeHtml(pendiente.folio || "") + "\" class=\"row g-3 align-items-end\">" +
                "<div class=\"col-md-3\"><label class=\"form-label fs-8 text-muted\">Cantidad fisica</label><input class=\"form-control form-control-solid\" name=\"cantidad_fisica\" type=\"number\" min=\"0\" step=\"0.0001\" value=\"" + Number(pendiente.cantidad_pendiente || 0).toFixed(4) + "\"></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label fs-8 text-muted\">Decision</label><select class=\"form-select form-select-solid\" name=\"decision\"><option value=\"ajustar_a_conteo\">Ajustar a conteo</option><option value=\"cerrar_sin_ajuste\">Cerrar sin ajuste</option><option value=\"mantener_pendiente\">Mantener pendiente</option></select></div>" +
                "<div class=\"col-md-4\"><label class=\"form-label fs-8 text-muted\">Motivo</label><input class=\"form-control form-control-solid\" name=\"motivo\" placeholder=\"Conteo fisico y evidencia\"></div>" +
                "<div class=\"col-md-2\"><button class=\"btn btn-light-primary w-100\" type=\"submit\"><i class=\"bi bi-calculator\"></i> Dry-run</button></div>" +
                "</form><div class=\"mt-4\" data-pendiente-pos-dryrun-result></div></div>";
        }
        return "<div class=\"d-flex flex-wrap gap-2 mb-5\">" +
            "<span class=\"badge " + estado.clase + " fs-7\">" + estado.texto + "</span>" +
            "<span class=\"badge badge-light-primary fs-7\">" + escapeHtml(pendiente.folio || "") + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Venta " + escapeHtml(pendiente.folio_venta || "-") + "</span>" +
            "</div>" +
            "<div class=\"row g-4 mb-6\"><div class=\"col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"fw-bold mb-2\">Producto</div><div>" + escapeHtml(pendiente.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(pendiente.descripcion || "") + "</div><div class=\"mt-3 text-muted fs-8\">Almacen: " + escapeHtml(pendiente.almacen || "-") + "</div></div></div>" +
            "<div class=\"col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"fw-bold mb-2\">Conteo y resolucion</div><div>Vendida: <span class=\"fw-bold\">" + Number(pendiente.cantidad_vendida || 0).toFixed(2) + "</span></div><div>Pendiente: <span class=\"fw-bold\">" + Number(pendiente.cantidad_pendiente || 0).toFixed(2) + "</span></div><div>Fisica validada: <span class=\"fw-bold\">" + Number(pendiente.cantidad_fisica_validada || 0).toFixed(2) + "</span></div><div class=\"text-muted fs-8 mt-2\">Movimiento ajuste: " + escapeHtml(pendiente.id_movimiento_ajuste || "-") + "</div></div></div></div>" +
            formularioDryRun +
            "<h4 class=\"fs-6 fw-bold mb-3\">Existencias actuales</h4><div class=\"table-responsive mb-6\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Existencia</th><th>Lote</th><th>Ubicacion</th><th>Cantidad</th><th>Disponible</th><th>Ult. mov.</th></tr></thead><tbody>" + existenciasHtml + "</tbody></table></div>" +
            "<h4 class=\"fs-6 fw-bold mb-3\">Eventos</h4><div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Fecha</th><th>Evento</th><th>Estado</th><th>Cantidad</th><th>Referencia</th></tr></thead><tbody>" + eventosHtml + "</tbody></table></div>";
    }
    function renderDryRunPendientePos(data) {
        var pendiente = data.pendiente || {};
        var propuesta = data.propuesta || {};
        var conteo = data.conteo || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var clase = bloqueos.length ? "alert-warning" : "alert-success";
        var partes = [
            "<div class=\"fw-bold mb-2\">Resultado dry-run</div>",
            "<div>Decision: <span class=\"fw-bold\">" + escapeHtml(conteo.decision || "-") + "</span></div>",
            "<div>Fisico: <span class=\"fw-bold\">" + Number(conteo.cantidad_fisica || 0).toFixed(4) + "</span> / ERP: <span class=\"fw-bold\">" + Number(conteo.disponible_erp_actual || 0).toFixed(4) + "</span></div>",
            "<div>Diferencia: <span class=\"fw-bold\">" + Number(conteo.diferencia_ajuste || 0).toFixed(4) + "</span></div>",
            "<div>Accion: " + (propuesta.requiere_ajuste ? "<span class=\"fw-bold\">" + escapeHtml(propuesta.tipo_ajuste || "") + " por " + Number(propuesta.cantidad_ajuste || 0).toFixed(4) + "</span>" : "<span class=\"fw-bold\">sin kardex</span>") + "</div>",
            "<div>Cierre pendiente: <span class=\"fw-bold\">" + (propuesta.cerrar_pendiente ? "si" : "no") + "</span></div>",
            "<div class=\"text-muted fs-8\">Referencia sugerida: " + escapeHtml(propuesta.referencia_sugerida || "-") + "</div>"
        ];
        if (bloqueos.length) { partes.push("<div class=\"mt-2 text-danger fs-8\">Bloqueos: " + bloqueos.map(escapeHtml).join(", ") + "</div>"); }
        if (avisos.length) { partes.push("<div class=\"mt-2 text-muted fs-8\">Avisos: " + avisos.map(escapeHtml).join(", ") + "</div>"); }
        if (!bloqueos.length && propuesta.cerrar_pendiente) {
            partes.push("<div class=\"border-top mt-4 pt-4\"><div class=\"fw-bold mb-3\">Preparar resolucion real</div>" +
                "<form data-pendiente-pos-resolver=\"" + escapeHtml(pendiente.folio || "") + "\" class=\"row g-3 align-items-end\">" +
                "<input type=\"hidden\" name=\"cantidad_fisica\" value=\"" + escapeHtml(conteo.cantidad_fisica || 0) + "\">" +
                "<input type=\"hidden\" name=\"decision\" value=\"" + escapeHtml(conteo.decision || "") + "\">" +
                "<input type=\"hidden\" name=\"motivo\" value=\"" + escapeHtml(conteo.motivo || "") + "\">" +
                "<div class=\"col-md-3\"><label class=\"form-label fs-8 text-muted\">Token</label><input class=\"form-control form-control-solid\" name=\"autorizar\" placeholder=\"INVENTARIO_POS...\"></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label fs-8 text-muted\">Respaldo</label><input class=\"form-control form-control-solid\" name=\"respaldo\" placeholder=\"UAT POS vigente\"></div>" +
                "<div class=\"col-md-4\"><label class=\"form-label fs-8 text-muted\">Confirmacion</label><input class=\"form-control form-control-solid\" name=\"confirmacion\" placeholder=\"RESOLVER PENDIENTE\"></div>" +
                "<div class=\"col-md-2\"><button class=\"btn btn-light-danger w-100\" type=\"submit\"><i class=\"bi bi-check2-circle\"></i> Resolver</button></div>" +
                "</form><div class=\"mt-4\" data-pendiente-pos-resolver-result></div></div>");
        }
        return "<div class=\"alert " + clase + " mb-0\">" + partes.join("") + "</div>";
    }
    function simularPendientePos(form) {
        var destino = form.querySelector("[data-pendiente-pos-dryrun-result]");
        destino = form.parentElement.querySelector("[data-pendiente-pos-dryrun-result]");
        destino.innerHTML = "<div class=\"text-muted fs-8\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Calculando propuesta</div>";
        post("/inventario/pos_pendiente_inventario_resolucion_dryrun_erp", {
            folio: form.getAttribute("data-pendiente-pos-dryrun") || "",
            cantidad_fisica: form.elements.cantidad_fisica.value,
            decision: form.elements.decision.value,
            motivo: form.elements.motivo.value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            destino.innerHTML = renderDryRunPendientePos(response.depurar || {});
        }).catch(function (error) {
            destino.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function resolverPendientePos(form) {
        var confirmacion = (form.elements.confirmacion.value || "").trim().toUpperCase();
        var destino = form.parentElement.querySelector("[data-pendiente-pos-resolver-result]");
        if (confirmacion !== "RESOLVER PENDIENTE") {
            destino.innerHTML = "<div class=\"alert alert-warning mb-0\">Escribe RESOLVER PENDIENTE para confirmar.</div>";
            return;
        }
        var mensaje = "Esta accion puede crear kardex y cerrar el pendiente POS. El backend volvera a validar permisos, token y respaldo.";
        var confirmar = window.Swal
            ? Swal.fire({text: mensaje, icon: "warning", showCancelButton: true, confirmButtonText: "Resolver", cancelButtonText: "Cancelar"}).then(function (r) { return !!r.isConfirmed; })
            : Promise.resolve(window.confirm(mensaje));
        confirmar.then(function (ok) {
            if (!ok) { return; }
            destino.innerHTML = "<div class=\"text-muted fs-8\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Enviando resolucion real</div>";
            post("/inventario/pos_pendiente_inventario_resolver_erp", {
                folio: form.getAttribute("data-pendiente-pos-resolver") || "",
                cantidad_fisica: form.elements.cantidad_fisica.value,
                decision: form.elements.decision.value,
                motivo: form.elements.motivo.value,
                autorizar: form.elements.autorizar.value,
                respaldo: form.elements.respaldo.value,
                confirmacion: form.elements.confirmacion.value
            }).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                destino.innerHTML = "<div class=\"alert alert-success mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Pendiente resuelto") + "</div><div class=\"fs-8 text-muted\">Recarga la bandeja para ver el estado actualizado.</div></div>";
                cargar();
            }).catch(function (error) {
                destino.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
            });
        });
    }
    function renderMovimientos(items) {
        document.getElementById("inventario_movimientos").innerHTML = items.map(function (item) {
            var clase = item.tipo_movimiento === "entrada" ? "badge-light-success" : "badge-light-danger";
            var signo = item.tipo_movimiento === "entrada" ? "+" : "-";
            var anterior = item.existencia_anterior === null || item.existencia_anterior === undefined ? "-" : Number(item.existencia_anterior || 0).toFixed(2);
            var nueva = item.existencia_nueva === null || item.existencia_nueva === undefined ? "-" : Number(item.existencia_nueva || 0).toFixed(2);
            var origenClase = item.origen_tipo === "preparacion_presentacion" ? "badge-light-primary" : "badge-light-secondary";
            return "<tr><td>" + escapeHtml(item.fecha_registro) + "</td><td><span class=\"badge " + clase + "\">" + escapeHtml(item.tipo_movimiento) + "</span><div class=\"mt-1\"><span class=\"badge " + origenClase + "\">" + escapeHtml(item.origen_tipo) + "</span></div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td><td class=\"fw-bold\">" + signo + Number(item.cantidad || 0).toFixed(2) + "</td>" +
                "<td>" + anterior + " / " + nueva + "</td><td><span class=\"fw-bold\">" + escapeHtml(item.referencia || "-") + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + "</div></td>" +
                "<td class=\"text-end\">" + botonTrazabilidad(item.codigo_existencia || item.referencia || item.sku) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin movimientos registrados</td></tr>";
    }
    function estadoEtiqueta(estado) {
        if (estado === "pegada") { return {clase: "badge-light-success", texto: "Pegada"}; }
        if (estado === "impresa") { return {clase: "badge-light-info", texto: "Impresa"}; }
        if (estado === "reimpresa") { return {clase: "badge-light-primary", texto: "Reimpresa"}; }
        if (estado === "cancelada") { return {clase: "badge-light-danger", texto: "Cancelada"}; }
        return {clase: "badge-light-warning", texto: "Pendiente impresion"};
    }
    function urlEtiquetado(item) {
        var codigo = item.codigo_etiqueta_interna || item.codigo_unico || item.folio_recepcion || "";
        var estado = item.estado_etiqueta || "";
        var params = new URLSearchParams({
            q: codigo,
            estado_etiqueta: estado,
            id_almacen: item.id_almacen || ""
        });
        return "/almacen/etiquetado?" + params.toString();
    }
    function renderUnidades(items) {
        var pendientes = items.filter(function (item) { return item.estado_etiqueta === "pendiente_impresion"; }).length;
        var impresas = items.filter(function (item) { return item.estado_etiqueta === "impresa" || item.estado_etiqueta === "reimpresa"; }).length;
        var pegadas = items.filter(function (item) { return item.estado_etiqueta === "pegada"; }).length;
        var abiertas = items.filter(function (item) { return item.estado_fisico === "abierta"; }).length;
        var cerradas = items.filter(function (item) { return item.estado_fisico === "cerrada"; }).length;
        document.getElementById("inventario_unidades_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Unidades " + items.length + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Cerradas " + cerradas + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Abiertas " + abiertas + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Pendientes " + pendientes + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Impresas " + impresas + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Pegadas " + pegadas + "</span>";
        document.getElementById("inventario_unidades").innerHTML = items.map(function (item) {
            var codigo = item.codigo_etiqueta_interna || item.serie_fabricante || item.codigo_unico || "-";
            var estado = estadoEtiqueta(item.estado_etiqueta);
            var fisico = estadoFisico(item.estado_fisico);
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(codigo) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_identidad || "-") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div></td>" +
                "<td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "") + "</div><div class=\"fw-semibold fs-8\">" + formatoContenidoUnidad(item) + "</div></td>" +
                "<td><span class=\"fw-bold\">" + escapeHtml(item.folio_recepcion || item.origen_tipo || "-") + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.folio_orden_compra || "") + "</div></td>" +
                "<td><span class=\"badge " + fisico.clase + " me-1\">" + fisico.texto + "</span><span class=\"badge " + estado.clase + "\">" + estado.texto + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.estatus || "") + "</div></td>" +
                "<td class=\"text-end\"><div class=\"d-inline-flex gap-2\"><a class=\"btn btn-sm btn-light-primary\" href=\"" + escapeHtml(urlEtiquetado(item)) + "\"><i class=\"bi bi-printer\"></i> Etiquetado</a>" + botonTrazabilidad(codigo) + "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\"><div class=\"fw-semibold\">Sin unidades etiquetadas</div><div class=\"fs-8 mt-1\">Esta pestana solo muestra SKUs con etiqueta interna. Revisa Existencias o Kardex para saldos sin etiqueta, agotados o movimientos de preparacion.</div></td></tr>";
    }
    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos().then(cargar);
        document.getElementById("inventario_recargar").addEventListener("click", cargar);
        document.getElementById("inventario_filtro_almacen").addEventListener("change", cargar);
        document.getElementById("inventario_filtro_estado_fisico").addEventListener("change", cargar);
        document.getElementById("inventario_filtro_agotadas").addEventListener("change", cargar);
        var timer;
        document.getElementById("inventario_filtro_buscar").addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(cargar, 250);
        });
        document.addEventListener("click", function (event) {
            var boton = event.target.closest ? event.target.closest("[data-trazabilidad]") : null;
            if (boton) {
                abrirTrazabilidad(boton.getAttribute("data-trazabilidad"));
                return;
            }
            var pendiente = event.target.closest ? event.target.closest("[data-pendiente-pos]") : null;
            if (pendiente) {
                abrirPendientePos(pendiente.getAttribute("data-pendiente-pos"));
            }
        });
        document.addEventListener("submit", function (event) {
            var formResolver = event.target.closest ? event.target.closest("[data-pendiente-pos-resolver]") : null;
            if (formResolver) {
                event.preventDefault();
                resolverPendientePos(formResolver);
                return;
            }
            var form = event.target.closest ? event.target.closest("[data-pendiente-pos-dryrun]") : null;
            if (!form) { return; }
            event.preventDefault();
            simularPendientePos(form);
        });
        if (window.location.hash === "#kardex") {
            var trigger = document.querySelector("[data-bs-target='#inventario_tab_kardex']");
            if (trigger) { bootstrap.Tab.getOrCreateInstance(trigger).show(); }
        }
        if (window.location.hash === "#unidades") {
            var unidades = document.querySelector("[data-bs-target='#inventario_tab_unidades']");
            if (unidades) { bootstrap.Tab.getOrCreateInstance(unidades).show(); }
        }
        if (window.location.hash === "#valuacion") {
            var valuacion = document.querySelector("[data-bs-target='#inventario_tab_valuacion']");
            if (valuacion) { bootstrap.Tab.getOrCreateInstance(valuacion).show(); }
        }
        if (window.location.hash === "#pendientes-pos") {
            var pendientesPos = document.querySelector("[data-bs-target='#inventario_tab_pendientes_pos']");
            if (pendientesPos) { bootstrap.Tab.getOrCreateInstance(pendientesPos).show(); }
        }
    });
})();
