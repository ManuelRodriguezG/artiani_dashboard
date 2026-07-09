"use strict";
(function () {
    function esc(value) { var d = document.createElement("div"); d.textContent = value == null ? "" : value; return d.innerHTML; }
    function leerPermisos() {
        return {
            editar: Number(document.getElementById("solicitud_permiso_editar").value || 0) === 1,
            crear: Number(document.getElementById("solicitud_permiso_crear").value || 0) === 1,
        };
    }
    var permisos = leerPermisos();
    var timerBusqueda;
    function cargarCatalogos() {
        return fetch("/compra/solicitudes_catalogos_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                var proveedores = r.depurar && Array.isArray(r.depurar.proveedores) ? r.depurar.proveedores : [];
                document.getElementById("solicitudes_proveedor").innerHTML = "<option value=\"\">Todos</option>" +
                    proveedores.map(function (x) {
                        return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
                    }).join("");
                document.getElementById("solicitudes_almacen").innerHTML = "<option value=\"\">Todos</option>" +
                    (r.depurar.almacenes || []).map(function (x) {
                        return "<option value=\"" + esc(x.id_almacen) + "\">" + esc(x.almacen) + "</option>";
                    }).join("");
                document.getElementById("solicitudes_solicitante").innerHTML = "<option value=\"\">Todos</option>" +
                    (r.depurar.usuarios || []).map(function (x) {
                        var etiqueta = x.area_departamento ? x.nombre + " - " + x.area_departamento : x.nombre;
                        return "<option value=\"" + esc(x.id_usuario) + "\">" + esc(etiqueta) + "</option>";
                    }).join("");
            });
    }
    function filtros() {
        return {
            q: document.getElementById("solicitudes_buscar").value.trim(),
            estatus: document.getElementById("solicitudes_estatus").value,
            prioridad: document.getElementById("solicitudes_prioridad").value,
            id_proveedor: document.getElementById("solicitudes_proveedor").value,
            id_almacen_destino: document.getElementById("solicitudes_almacen").value,
            solicitado_por: document.getElementById("solicitudes_solicitante").value,
            fecha_desde: document.getElementById("solicitudes_fecha_desde").value,
            fecha_hasta: document.getElementById("solicitudes_fecha_hasta").value,
            con_orden: document.getElementById("solicitudes_con_orden").value,
            productos_nuevos: document.getElementById("solicitudes_productos_nuevos").value
        };
    }
    function cargar() {
        fetch("/compra/solicitudes_listar_erp?" + new URLSearchParams(filtros()), {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                var rows = r.depurar || [];
                document.getElementById("solicitudes_body").innerHTML = rows.map(function (x) {
                    var editable = x.estatus === "borrador" && permisos.editar;
                    var puedeGenerar = x.estatus === "aprobada" && permisos.crear && !x.id_orden_compra;
                    var orden = x.id_orden_compra
                        ? "<a href=\"/compra/ver_orden_compra/" + esc(x.id_orden_compra) + "\" class=\"badge badge-light-success\">" + esc(x.folio_orden || "Orden") + "</a>"
                        : "<span class=\"badge badge-light-warning\">Sin orden</span>";
                    var nuevos = Number(x.productos_nuevos || 0) > 0
                        ? "<div class=\"mt-1\"><span class=\"badge badge-light-warning\">" + esc(x.productos_nuevos) + " nuevo(s)</span></div>"
                        : "";
                    var acciones = "<a class=\"btn btn-sm btn-light-primary\" href=\"/compra/solicitud_imprimir_erp/" + esc(x.id_solicitud) +
                        "\" title=\"Documento formal\" target=\"_blank\" rel=\"noopener\"><i class=\"bi bi-file-earmark-text me-1\"></i>Imprimir</a> " +
                        "<a class=\"btn btn-sm btn-light-success\" href=\"/compra/mostrar_solicitud/" + esc(x.id_solicitud) +
                        "\" title=\"Ver solicitud\"><i class=\"bi bi-eye me-1\"></i>Ver</a>";
                    if (editable) {
                        acciones += " <a class=\"btn btn-sm btn-light-info\" href=\"/compra/editar_solicitud/" + esc(x.id_solicitud) +
                            "\" title=\"Editar solicitud\"><i class=\"bi bi-pencil-square me-1\"></i>Editar</a>";
                    }
                    if (puedeGenerar) {
                        acciones += " <a class=\"btn btn-sm btn-light-warning\" href=\"/compra/mostrar_solicitud/" + esc(x.id_solicitud) +
                            "\" title=\"Generar desde la solicitud\"><i class=\"bi bi-file-earmark-plus me-1\"></i>Orden</a>";
                    }
                    var solicitante = "<div class=\"fw-bold\">" + esc(x.solicitante_nombre || "-") + "</div>" +
                        (x.solicitante_area ? "<div class=\"text-muted fs-8\">" + esc(x.solicitante_area) + "</div>" : "");
                    return "<tr><td class=\"fw-bold\">" + esc(x.folio) + "</td><td>" + solicitante + "</td><td>" + esc(x.proveedor) +
                        "</td><td>" + esc(x.almacen || "-") + "</td><td>" + esc(x.fecha_requerida || "-") +
                        "</td><td><span class=\"badge badge-light-primary\">" + esc(x.prioridad) + "</span></td><td>" + esc(x.total_partidas) + nuevos +
                        "</td><td>" + orden + "</td><td class=\"text-end fw-bold\">$" + Number(x.subtotal_estimado || 0).toFixed(2) + "</td><td><span class=\"badge badge-light\">" + esc(x.estatus) +
                        "</span></td><td class=\"text-end text-nowrap\">" + acciones + "</td></tr>";
                }).join("") || "<tr><td colspan=\"11\" class=\"text-center text-muted py-8\">Sin solicitudes</td></tr>";
            });
    }
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("solicitudes_buscar").addEventListener("input", function () {
            clearTimeout(timerBusqueda);
            timerBusqueda = setTimeout(cargar, 250);
        });
        ["solicitudes_estatus", "solicitudes_prioridad", "solicitudes_proveedor", "solicitudes_almacen",
            "solicitudes_solicitante", "solicitudes_fecha_desde",
            "solicitudes_fecha_hasta", "solicitudes_con_orden", "solicitudes_productos_nuevos"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", cargar);
        });
        cargarCatalogos().then(cargar);
    });
})();
