"use strict";
(function () {
    var storageKey = "erp_produccion_peceras_borradores_v1";
    var perfiles = [];

    function el(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function numero(value) {
        var parsed = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(parsed) ? parsed : 0;
    }
    function formato(value, decimales) {
        return Number(numero(value).toFixed(decimales == null ? 2 : decimales)).toString();
    }
    function cargar() {
        try {
            perfiles = JSON.parse(localStorage.getItem(storageKey) || "[]");
        } catch (e) {
            perfiles = [];
        }
    }
    function guardar() {
        localStorage.setItem(storageKey, JSON.stringify(perfiles));
    }
    function textoMedidas(perfil) {
        var d = perfil.datos || {};
        return formato(d.largo, 0) + " x " + formato(d.fondo, 0) + " x " + formato(d.alto, 0) + " cm, " + formato(d.espesor, 0) + " mm";
    }
    function render() {
        var q = String(el("peceras_perfiles_buscar").value || "").toLowerCase();
        var lista = el("peceras_perfiles_lista");
        var visibles = perfiles.filter(function (perfil) {
            var d = perfil.datos || {};
            var texto = [perfil.nombre, d.proveedor, textoMedidas(perfil)].join(" ").toLowerCase();
            return texto.indexOf(q) >= 0;
        });
        el("peceras_perfiles_vacio").classList.toggle("d-none", visibles.length > 0);
        lista.innerHTML = visibles.map(function (perfil) {
            var d = perfil.datos || {};
            var piezas = Array.isArray(perfil.piezas) ? perfil.piezas : [];
            var totalPiezas = piezas.reduce(function (total, pieza) {
                return total + (Number(pieza.cantidad || pieza.cantidad_por_pecera || 0) * Number(d.cantidad || 1));
            }, 0);
            return "<div class=\"col-md-6 col-xl-4\">" +
                "<article class=\"card h-100\"><div class=\"card-body d-flex flex-column gap-3\">" +
                "<div><div class=\"fw-bold fs-5\">" + escapeHtml(perfil.nombre || "Pecera sin nombre") + "</div>" +
                "<div class=\"text-muted\">" + escapeHtml(textoMedidas(perfil)) + "</div></div>" +
                "<div class=\"d-flex flex-wrap gap-2\"><span class=\"badge badge-light-primary\">" + escapeHtml(d.cantidad || 1) + " pecera(s)</span><span class=\"badge badge-light-info\">" + escapeHtml(totalPiezas) + " piezas</span><span class=\"badge badge-light\">" + escapeHtml(d.proveedor || "Sin proveedor") + "</span></div>" +
                "<div class=\"d-flex flex-wrap gap-2 mt-auto\">" +
                "<a class=\"btn btn-sm btn-primary\" href=\"/produccion/peceras?perfil=" + encodeURIComponent(perfil.id) + "\"><i class=\"bi bi-pencil\"></i> Editar</a>" +
                "<a class=\"btn btn-sm btn-light-primary\" href=\"/produccion/peceras_pedido_vidrio?perfil=" + encodeURIComponent(perfil.id) + "\"><i class=\"bi bi-layers\"></i> Pedir</a>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-eliminar-perfil=\"" + escapeHtml(perfil.id) + "\"><i class=\"bi bi-trash\"></i></button>" +
                "</div></div></article></div>";
        }).join("");
    }
    function init() {
        cargar();
        render();
        el("peceras_perfiles_buscar").addEventListener("input", render);
        document.addEventListener("click", function (event) {
            var boton = event.target.closest("[data-eliminar-perfil]");
            if (!boton) {
                return;
            }
            var id = boton.getAttribute("data-eliminar-perfil");
            perfiles = perfiles.filter(function (perfil) { return String(perfil.id) !== String(id); });
            guardar();
            render();
        });
        el("peceras_perfiles_limpiar").addEventListener("click", function () {
            perfiles = perfiles.filter(function (perfil) { return perfil && perfil.id; });
            guardar();
            render();
        });
    }
    document.addEventListener("DOMContentLoaded", init);
})();
