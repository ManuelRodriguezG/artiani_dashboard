"use strict";
(function () {
    var storageKey = "erp_mini_inventarios_operativos_v2";
    var docs = {};
    var activeId = "";

    function esc(v) { var d = document.createElement("div"); d.textContent = v == null ? "" : String(v); return d.innerHTML; }
    function num(v) { var n = Number(String(v == null ? "" : v).replace(",", ".")); return Number.isFinite(n) ? n : 0; }
    function fmt(v) { return Number(num(v).toFixed(3)).toString(); }
    function nowIso() { return new Date().toISOString(); }
    function estadoLabel(estado) {
        var mapa = {
            borrador: "Borrador",
            en_revision: "En revision",
            listo_decision: "Listo para decision",
            cerrado_manual: "Cerrado manual"
        };
        return mapa[estado] || "Borrador";
    }

    function cargarLocal() {
        try {
            var data = JSON.parse(localStorage.getItem(storageKey) || "{}") || {};
            docs = data.docs || {};
            activeId = data.activeId || "";
        } catch (e) {
            docs = {};
            activeId = "";
        }
    }

    function guardarLocal() {
        localStorage.setItem(storageKey, JSON.stringify({activeId: activeId, docs: docs}));
    }

    function calcularSugerido(item) {
        var c = item.captura || {};
        var listo = c.listo === "" || c.listo == null ? num(item.existencia_sistema || 0) : num(c.listo);
        var minimo = c.minimo === "" || c.minimo == null ? num(item.stock_minimo || 0) : num(c.minimo);
        var maximo = c.maximo === "" || c.maximo == null ? (item.stock_maximo === null || item.stock_maximo === "" ? 0 : num(item.stock_maximo)) : num(c.maximo);
        var reorden = num(item.punto_reorden || 0);
        var umbral = reorden > 0 ? reorden : minimo;
        if (umbral <= 0 && maximo <= 0) { return 0; }
        if (umbral > 0 && listo > umbral) { return 0; }
        if (maximo > listo) { return Number((maximo - listo).toFixed(3)); }
        if (umbral > listo) { return Number((umbral - listo).toFixed(3)); }
        return 0;
    }

    function accionFinal(item, sugerido) {
        var c = item.captura || {};
        if (c.accion && c.accion !== "auto") { return c.accion; }
        if (sugerido <= 0) { return "sin_accion"; }
        if (item.accion_sugerida === "reempacar" || item.accion_sugerida === "abrir_empaque" || item.accion_sugerida === "etiquetar") {
            return item.accion_sugerida;
        }
        return "comprar";
    }

    function resumenDoc(d) {
        var items = Object.keys(d.items || {}).map(function (id) { return d.items[id]; });
        var tareas = 0;
        var comprar = 0;
        items.forEach(function (item) {
            var sugerido = calcularSugerido(item);
            var accion = accionFinal(item, sugerido);
            if (accion !== "sin_accion") { tareas++; }
            if (accion === "comprar") { comprar++; }
        });
        return {productos: items.length, tareas: tareas, comprar: comprar};
    }

    function docTextoBusqueda(d) {
        var partes = [d.nombre, d.estado, d.id];
        Object.keys(d.items || {}).forEach(function (id) {
            var item = d.items[id];
            var c = item.captura || {};
            partes.push(item.sku, item.nombre_sku, item.producto, item.proveedor, c.responsable, c.nota);
        });
        return partes.join(" ").toLowerCase();
    }

    function card(titulo, valor, icono, clase) {
        return "<div class=\"border rounded px-4 py-3 " + clase + "\"><div class=\"d-flex align-items-center gap-3\"><i class=\"bi " + icono + " fs-2\"></i><div><div class=\"fw-bold fs-5\">" + esc(valor) + "</div><div class=\"text-muted fs-8\">" + esc(titulo) + "</div></div></div></div>";
    }

    function render() {
        var q = document.getElementById("mini_inv_lista_buscar").value.trim().toLowerCase();
        var estado = document.getElementById("mini_inv_lista_estado").value;
        var rows = Object.keys(docs).map(function (id) { return docs[id]; }).filter(function (d) {
            if (estado && d.estado !== estado) { return false; }
            if (q && docTextoBusqueda(d).indexOf(q) === -1) { return false; }
            return true;
        }).sort(function (a, b) {
            return String(b.updated_at || "").localeCompare(String(a.updated_at || ""));
        });

        var totalProductos = 0;
        var totalTareas = 0;
        var totalComprar = 0;
        rows.forEach(function (d) {
            var r = resumenDoc(d);
            totalProductos += r.productos;
            totalTareas += r.tareas;
            totalComprar += r.comprar;
        });
        document.getElementById("mini_inv_lista_resumen").innerHTML =
            card("Mini inventarios", rows.length, "bi-journals", "bg-light-primary") +
            card("Productos", totalProductos, "bi-box-seam", "bg-light-info") +
            card("Tareas", totalTareas, "bi-list-task", "bg-light-warning") +
            card("Compra", totalComprar, "bi-cart-plus", "bg-light-danger");

        document.getElementById("mini_inv_lista_estado_texto").textContent = rows.length + " mini inventario(s) local(es).";
        document.getElementById("mini_inv_lista_body").innerHTML = rows.map(function (d) {
            var r = resumenDoc(d);
            var updated = d.updated_at ? new Date(d.updated_at).toLocaleString("es-MX") : "-";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(d.nombre || d.id) + "</div><div class=\"text-muted fs-8\">" + esc(d.id) + "</div></td>" +
                "<td><span class=\"badge badge-light-primary\">" + esc(estadoLabel(d.estado)) + "</span></td>" +
                "<td class=\"text-end\">" + esc(r.productos) + "</td>" +
                "<td class=\"text-end\">" + esc(r.tareas) + "</td>" +
                "<td class=\"text-end\">" + esc(r.comprar) + "</td>" +
                "<td>" + esc(updated) + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\"><a class=\"btn btn-sm btn-primary\" href=\"/operacion/mini_inventario#" + encodeURIComponent(d.id) + "\"><i class=\"bi bi-pencil-square\"></i> Ver / editar</a><button class=\"btn btn-sm btn-light-danger\" data-mini-delete=\"" + esc(d.id) + "\" type=\"button\"><i class=\"bi bi-trash\"></i></button></div></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">No hay mini inventarios locales. Crea uno nuevo para empezar.</td></tr>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarLocal();
        render();
        document.getElementById("mini_inv_lista_nuevo").addEventListener("click", function () {
            window.location.href = "/operacion/mini_inventario#nuevo";
        });
        document.getElementById("mini_inv_lista_buscar").addEventListener("input", render);
        document.getElementById("mini_inv_lista_estado").addEventListener("change", render);
        document.getElementById("mini_inv_lista_body").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-mini-delete]");
            if (!btn) { return; }
            var id = btn.getAttribute("data-mini-delete");
            if (!docs[id] || !window.confirm("Eliminar este mini inventario local?")) { return; }
            delete docs[id];
            if (activeId === id) { activeId = ""; }
            guardarLocal();
            render();
        });
    });
})();
