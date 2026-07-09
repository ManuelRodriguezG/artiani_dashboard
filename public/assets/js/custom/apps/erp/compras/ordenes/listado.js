"use strict";
(function () {
    function esc(value) { var d = document.createElement("div"); d.textContent = value == null ? "" : value; return d.innerHTML; }
    function cargar() {
        var puedeEditar = document.getElementById("ordenes_puede_editar").value === "1";
        var puedeSeguimiento = document.getElementById("ordenes_puede_seguimiento").value === "1";
        var q = document.getElementById("ordenes_buscar").value.trim();
        var estatus = document.getElementById("ordenes_estatus").value;
        fetch("/compra/ordenes_listar_erp?" + new URLSearchParams({q: q, estatus: estatus}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                var rows = r.depurar || [];
                document.getElementById("ordenes_body").innerHTML = rows.map(function (x) {
                    var acciones = "<a class=\"btn btn-sm btn-light-primary me-2\" href=\"/compra/ver_orden_compra/" +
                        esc(x.id_orden_compra) + "\">Ver</a>";
                    if (puedeEditar && x.estatus === "borrador") {
                        acciones += "<a class=\"btn btn-sm btn-light\" href=\"/compra/editar_orden_compra/" +
                            esc(x.id_orden_compra) + "\">Editar</a>";
                    } else if (puedeSeguimiento && x.estatus !== "cancelada") {
                        acciones += "<a class=\"btn btn-sm btn-light\" href=\"/compra/seguimiento_orden_compra/" +
                            esc(x.id_orden_compra) + "\">Seguimiento</a>";
                    }
                    return "<tr><td class=\"fw-bold\">" + esc(x.folio) + "</td><td>" + esc(x.folio_solicitud || "-") +
                        "</td><td>" + esc(x.proveedor) + "</td><td>" + esc(x.almacen || "-") + "</td><td>" + esc((x.fecha_entrega_estimada || "").slice(0, 10) || "-") +
                        "</td><td>" + esc(x.total_partidas) + "</td><td class=\"text-end fw-bold\">$" + Number(x.total || 0).toFixed(2) +
                        "</td><td><span class=\"badge badge-light-primary\">" + esc(x.estatus) +
                        "</span></td><td class=\"text-end text-nowrap\">" + acciones + "</td></tr>";
                }).join("") || "<tr><td colspan=\"9\" class=\"text-center text-muted py-8\">Sin ordenes de compra</td></tr>";
            });
    }
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("ordenes_buscar").addEventListener("input", cargar);
        document.getElementById("ordenes_estatus").addEventListener("change", cargar);
        cargar();
    });
})();
