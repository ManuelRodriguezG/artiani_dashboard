"use strict";
(function () {
    var perfiles = [];
    var endpoints = {listar: "/produccion/peceras_perfiles_listar_erp", archivar: "/produccion/pecera_archivar_erp"};

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
        return fetch(endpoints.listar, {credentials: "same-origin", headers: {"Accept": "application/json"}}).then(function (response) {
            if (!response.ok) { throw new Error("No fue posible consultar los perfiles."); }
            return response.json();
        }).then(function (respuesta) {
            perfiles = respuesta && respuesta.depurar && Array.isArray(respuesta.depurar.perfiles) ? respuesta.depurar.perfiles : [];
            render();
        }).catch(function (error) {
            perfiles = [];
            el("peceras_perfiles_vacio").textContent = error.message;
            render();
        });
    }
    function textoMedidas(perfil) {
        var d = perfil.datos || {};
        return formato(d.largo, 0) + " x " + formato(d.fondo, 0) + " x " + formato(d.alto, 0) + " cm, " + formato(d.espesor, 0) + " mm";
    }
    function renderVariantesHistoricas(perfil) {
        var datos = perfil.datos || {};
        var variantes = Array.isArray(datos.variantes_historicas) ? datos.variantes_historicas : [];
        if (!variantes.length) {
            return "<div class=\"text-muted small\">Sin variantes historicas registradas.</div>";
        }
        var filas = variantes.map(function (variante) {
            return "<tr><td>" + escapeHtml(variante[0] || "Pieza") + "</td><td>" + escapeHtml(formato(variante[1], 2)) + " x " + escapeHtml(formato(variante[2], 2)) + " cm</td><td>" + escapeHtml(formato(variante[3], 2)) + " mm</td></tr>";
        }).join("");
        return "<details class=\"border-top pt-3\"><summary class=\"fw-semibold small\">Ver " + escapeHtml(variantes.length) + " variante(s) historica(s)</summary><div class=\"table-responsive mt-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr><th>Pieza</th><th>Medida</th><th>Espesor</th></tr></thead><tbody>" + filas + "</tbody></table></div></details>";
    }
    function render() {
        var q = String(el("peceras_perfiles_buscar").value || "").toLowerCase();
        var lista = el("peceras_perfiles_lista");
        var visibles = perfiles.filter(function (perfil) {
            var d = perfil.datos || {};
            var texto = [perfil.codigo, perfil.nombre, d.proveedor, textoMedidas(perfil)].join(" ").toLowerCase();
            return texto.indexOf(q) >= 0;
        });
        el("peceras_perfiles_vacio").classList.toggle("d-none", visibles.length > 0);
        lista.innerHTML = visibles.map(function (perfil) {
            var d = perfil.datos || {};
            var piezas = Array.isArray(perfil.piezas) ? perfil.piezas : [];
            var totalPiezas = piezas.reduce(function (total, pieza) {
                return total + (Number(pieza.cantidad || pieza.cantidad_por_pecera || 0) * Number(d.cantidad || 1));
            }, 0);
            var aliasHistoricos = Array.isArray(d.alias_historicos) && d.alias_historicos.length ? "<div class=\"text-muted small\">Alias historicos: " + escapeHtml(d.alias_historicos.join(", ")) + "</div>" : "";
            return "<div class=\"col-md-6 col-xl-4\">" +
                "<article class=\"card h-100\"><div class=\"card-body d-flex flex-column gap-3\">" +
                "<div><div class=\"fw-bold fs-5\">" + escapeHtml(perfil.codigo || "Sin código") + "</div><div class=\"fw-bold\">" + escapeHtml(perfil.nombre || "Pecera sin nombre") + "</div>" +
                "<div class=\"text-muted\">" + escapeHtml(textoMedidas(perfil)) + "</div>" + aliasHistoricos + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2\"><span class=\"badge badge-light-primary\">" + escapeHtml(d.cantidad || 1) + " pecera(s)</span><span class=\"badge badge-light-info\">" + escapeHtml(totalPiezas) + " piezas</span><span class=\"badge badge-light\">" + escapeHtml(d.proveedor || "Sin proveedor") + "</span></div>" +
                renderVariantesHistoricas(perfil) +
                "<div class=\"d-flex flex-wrap gap-2 mt-auto\">" +
                "<a class=\"btn btn-sm btn-primary\" href=\"/produccion/peceras?perfil=" + encodeURIComponent(perfil.id) + "\"><i class=\"bi bi-pencil\"></i> Editar</a>" +
                "<a class=\"btn btn-sm btn-light-primary\" href=\"/produccion/peceras_pedido_vidrio?perfil=" + encodeURIComponent(perfil.id) + "\"><i class=\"bi bi-layers\"></i> Pedir</a>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-eliminar-perfil=\"" + escapeHtml(perfil.id) + "\"><i class=\"bi bi-archive\"></i></button>" +
                "</div></div></article></div>";
        }).join("");
    }
    function init() {
        cargar();
        el("peceras_perfiles_buscar").addEventListener("input", render);
        document.addEventListener("click", function (event) {
            var boton = event.target.closest("[data-eliminar-perfil]");
            if (!boton) {
                return;
            }
            var id = boton.getAttribute("data-eliminar-perfil");
            var body = new URLSearchParams();
            body.append("id_pecera", id);
            fetch(endpoints.archivar, {method: "POST", credentials: "same-origin", headers: {"Accept": "application/json", "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""}, body: body.toString()}).then(function (response) {
                if (!response.ok) { throw new Error("No fue posible archivar el perfil."); }
                return response.json();
            }).then(cargar).catch(function (error) { alert(error.message); });
        });
        el("peceras_perfiles_limpiar").addEventListener("click", function () {
            cargar();
        });
    }
    document.addEventListener("DOMContentLoaded", init);
})();
