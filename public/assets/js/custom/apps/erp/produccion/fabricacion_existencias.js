"use strict";
(function () {
    var endpoint = "/produccion/fabricacion_existencias_listar_erp";
    var guardarEndpoint = "/produccion/fabricacion_existencia_guardar_erp";
    var perfiles = [];

    function el(id) { return document.getElementById(id); }
    function numero(value) { var n = Number(String(value == null ? "" : value).replace(",", ".")); return Number.isFinite(n) ? n : 0; }
    function entero(value) { return Math.max(0, Math.round(numero(value))); }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function formato(value) { return Number(numero(value).toFixed(2)).toString(); }
    function jsonResponse(response) { if (!response.ok) { throw new Error("No fue posible consultar existencias."); } return response.json(); }

    function cargar() {
        return fetch(endpoint, {credentials: "same-origin", headers: {"Accept": "application/json"}}).then(jsonResponse).then(function (respuesta) {
            perfiles = respuesta && respuesta.depurar && Array.isArray(respuesta.depurar.perfiles) ? respuesta.depurar.perfiles : [];
            render();
        }).catch(function (error) { alert(error.message); });
    }
    function guardar(input) {
        var body = new URLSearchParams();
        Object.keys(input).forEach(function (key) { body.append(key, input[key]); });
        return fetch(guardarEndpoint, {method: "POST", credentials: "same-origin", headers: {"Accept": "application/json", "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""}, body: body.toString()}).then(jsonResponse).then(function (respuesta) {
            if (respuesta.error) { throw new Error(respuesta.mensaje || "No fue posible guardar la existencia."); }
            return cargar();
        }).catch(function (error) { alert(error.message); });
    }
    function coincide(perfil, busqueda) {
        var texto = [perfil.codigo, perfil.nombre].concat((perfil.piezas || []).map(function (pieza) { return pieza.nombre; })).join(" ").toLowerCase();
        return !busqueda || texto.indexOf(busqueda) >= 0;
    }
    function render() {
        var busqueda = String(el("fabricacion_existencias_buscar").value || "").trim().toLowerCase();
        var visibles = perfiles.filter(function (perfil) { return coincide(perfil, busqueda); });
        el("fabricacion_existencias_vacio").classList.toggle("d-none", visibles.length > 0);
        el("fabricacion_existencias_lista").innerHTML = visibles.map(renderPerfil).join("");
    }
    function renderPerfil(perfil) {
        var piezas = perfil.piezas || [];
        var faltantes = piezas.reduce(function (total, pieza) { return total + entero(pieza.faltante_sugerido); }, 0);
        return "<div class=\"border rounded p-4 mb-5\"><div class=\"d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4\"><div><div class=\"fw-bold fs-4\">" + escapeHtml(perfil.codigo || perfil.nombre) + "</div><div class=\"text-muted\">" + escapeHtml(perfil.nombre || "Perfil") + " | " + escapeHtml(formato(perfil.espesor)) + " mm</div></div><div class=\"d-flex flex-wrap gap-2\"><span class=\"badge badge-light-info\">Juegos posibles: " + escapeHtml(perfil.juegos_posibles) + "</span><span class=\"badge badge-light-warning\">Faltante sugerido: " + escapeHtml(faltantes) + " piezas</span></div></div>" +
            "<div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle gy-3\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Pieza</th><th>Medida</th><th>Por juego</th><th>Disponible</th><th>Meta juegos</th><th>Faltante sugerido</th><th></th></tr></thead><tbody>" + piezas.map(renderPieza).join("") + "</tbody></table></div></div>";
    }
    function renderPieza(pieza) {
        var id = escapeHtml(pieza.id_pecera_pieza);
        return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(pieza.nombre) + "</div><div class=\"text-muted small\">" + escapeHtml(pieza.observaciones || "") + "</div></td><td>" + escapeHtml(formato(pieza.largo) + " x " + formato(pieza.ancho) + " cm | " + formato(pieza.espesor) + " mm") + "</td><td>" + escapeHtml(pieza.cantidad_por_juego) + "</td><td><input class=\"form-control form-control-sm text-center\" style=\"max-width:110px\" inputmode=\"numeric\" data-existencia=\"cantidad_disponible\" data-id=\"" + id + "\" value=\"" + escapeHtml(pieza.cantidad_disponible) + "\"></td><td><input class=\"form-control form-control-sm text-center\" style=\"max-width:110px\" inputmode=\"numeric\" data-existencia=\"meta_juegos\" data-id=\"" + id + "\" value=\"" + escapeHtml(pieza.meta_juegos) + "\"></td><td><span class=\"badge badge-light-warning\">" + escapeHtml(pieza.faltante_sugerido) + "</span></td><td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-guardar-existencia=\"" + id + "\"><i class=\"bi bi-save\"></i> Guardar</button></td></tr>";
    }
    document.addEventListener("DOMContentLoaded", function () {
        cargar();
        el("fabricacion_existencias_actualizar").addEventListener("click", cargar);
        el("fabricacion_existencias_buscar").addEventListener("input", render);
        el("fabricacion_existencias_lista").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-guardar-existencia]");
            if (!boton) { return; }
            var id = boton.getAttribute("data-guardar-existencia");
            var fila = boton.closest("tr");
            var datos = {id_pecera_pieza: id, cantidad_disponible: fila.querySelector('[data-existencia="cantidad_disponible"]').value, meta_juegos: fila.querySelector('[data-existencia="meta_juegos"]').value, observaciones: ""};
            guardar(datos);
        });
    });
})();
