"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-21
     * Proposito: listar gastos/cargos de compra sincronizados desde partidas no inventariables.
     * Impacto: UI Compras; solo lectura para seguimiento operativo.
     */
    function esc(value) { var d = document.createElement("div"); d.textContent = value == null ? "" : value; return d.innerHTML; }
    function money(value) { return "$" + Number(value || 0).toFixed(2); }
    function label(value) {
        return {
            cargo: "Cargo",
            servicio: "Servicio",
            adicional: "Adicional",
            no_inventariable: "No inventariable",
            gasto: "Gasto",
            rentabilidad: "Rentabilidad",
            prorrateo_inventario: "Prorrateo inventario",
            pendiente_finanzas: "Pendiente finanzas",
            validado_finanzas: "Validado finanzas",
            aplicado_costos: "Aplicado costos",
            cancelado: "Cancelado"
        }[value] || value || "-";
    }
    function cargar() {
        var params = new URLSearchParams({
            q: document.getElementById("gastos_buscar").value.trim(),
            tipo_cargo: document.getElementById("gastos_tipo").value,
            tratamiento: document.getElementById("gastos_tratamiento").value,
            estatus: document.getElementById("gastos_estatus").value
        });
        fetch("/compra/gastos_compra_listar_erp?" + params.toString(), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                var data = r.depurar || {};
                var resumen = data.resumen || {};
                var rows = data.items || [];
                document.getElementById("gastos_resumen_registros").textContent = String(resumen.registros || rows.length || 0);
                document.getElementById("gastos_resumen_subtotal").textContent = money(resumen.subtotal);
                document.getElementById("gastos_resumen_impuestos").textContent = money(resumen.impuestos);
                document.getElementById("gastos_resumen_total").textContent = money(resumen.total);
                document.getElementById("gastos_body").innerHTML = rows.map(function (x) {
                    var impuesto = Math.max(Number(x.total || 0) - Number(x.subtotal || 0) + Number(x.descuento || 0), 0);
                    return "<tr><td class=\"fw-bold\">" + esc(x.folio || x.id_orden_compra) +
                        "</td><td>" + esc(x.proveedor || "-") +
                        "</td><td><span class=\"badge badge-light-info\">" + esc(label(x.tipo_cargo)) + "</span>" +
                        "</td><td>" + esc(x.descripcion) +
                        "</td><td>" + esc(label(x.tratamiento)) +
                        "</td><td><span class=\"badge badge-light-primary\">" + esc(label(x.estatus)) + "</span>" +
                        "</td><td class=\"text-end\">" + money(x.subtotal) +
                        "</td><td class=\"text-end\">" + money(impuesto) +
                        "</td><td class=\"text-end fw-bold\">" + money(x.total) +
                        "</td><td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"/compra/ver_orden_compra/" + esc(x.id_orden_compra) + "\">Ver orden</a></td></tr>";
                }).join("") || "<tr><td colspan=\"10\" class=\"text-center text-muted py-8\">Sin gastos de compra</td></tr>";
            });
    }
    document.addEventListener("DOMContentLoaded", function () {
        ["gastos_buscar", "gastos_tipo", "gastos_tratamiento", "gastos_estatus"].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) { return; }
            el.addEventListener(id === "gastos_buscar" ? "input" : "change", cargar);
        });
        cargar();
    });
})();