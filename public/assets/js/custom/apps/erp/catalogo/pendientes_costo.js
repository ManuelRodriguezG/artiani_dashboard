"use strict";

(function () {
    var timer = null;
    var items = [];
    var puedeVerCostos = window.PENDIENTES_COSTO_PUEDE_VER_COSTOS === true;

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function money(value) {
        if (value === null || value === "" || Number(value) <= 0) { return "Sin costo"; }
        return "$" + Number(value).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 4});
    }

    function request(params) {
        return fetch("/catalogoerp/pendientes_costo_datos?" + new URLSearchParams(params).toString(), {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function badge(alerta) {
        var clase = alerta.severidad === "danger" ? "danger" : alerta.severidad === "warning" ? "warning" : alerta.severidad === "info" ? "info" : "secondary";
        return "<span class=\"badge badge-light-" + clase + " me-1 mb-1\">" + escapeHtml(alerta.texto) + "</span>";
    }

    function renderResumen(conteos) {
        var tarjetas = [
            ["sin_costo", "Sin costo", "danger"], ["sin_proveedor", "Sin proveedor", "danger"], ["proveedor_sin_costo", "Proveedor sin costo", "warning"],
            ["costo_pendiente_validar", "Por validar", "warning"], ["derivado", "Derivados", "info"], ["sin_precio_publico", "Sin precio público", "primary"]
        ];
        document.getElementById("pendientes_costo_resumen").innerHTML = tarjetas.map(function (tarjeta) {
            return "<div class=\"col-md-6 col-xl-2\"><div class=\"card card-flush h-100 border border-" + tarjeta[2] + " border-opacity-25\"><div class=\"card-body py-4\"><span class=\"text-muted fs-7\">" + tarjeta[1] + "</span><div class=\"fs-2hx fw-bold text-" + tarjeta[2] + "\">" + escapeHtml(conteos[tarjeta[0]] || 0) + "</div></div></div></div>";
        }).join("");
    }

    function render() {
        var lista = document.getElementById("pendientes_costo_lista");
        document.getElementById("pendientes_costo_total").textContent = items.length + " filas";
        if (!items.length) { lista.innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">No hay pendientes para estos filtros.</td></tr>"; return; }
        lista.innerHTML = items.map(function (item) {
            var proveedor = item.proveedores || "Sin proveedor relacionado";
            var costo = !puedeVerCostos ? "<span class=\"badge badge-light\">Costo restringido</span>" : money(item.costo_actual) + (item.fuente_costo ? "<div class=\"text-muted fs-8\">Fuente: " + escapeHtml(item.fuente_costo) + "</div>" : "");
            var precio = item.precio_publico === null ? "<span class=\"badge badge-light-warning\">Sin precio</span>" : money(item.precio_publico);
            var acciones = item.siguientes_acciones.map(function (accion) { return "<div class=\"mb-1\"><i class=\"bi bi-arrow-right-short\"></i>" + escapeHtml(accion) + "</div>"; }).join("");
            var costoLink = puedeVerCostos ? "<a class=\"btn btn-sm btn-light\" href=\"" + escapeHtml(item.url_rentabilidad) + "\">Costo</a>" : "";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"pendientes-costo-producto\">" + escapeHtml(item.producto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_sku || item.codigo_producto || "") + "</div></td>" +
                "<td><div>" + escapeHtml(item.marca || "Sin marca") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.categoria || "Sin categoria") + "</div></td>" +
                "<td><div>" + escapeHtml(proveedor) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.proveedores_activos || 0) + " relacion(es) activas</div></td>" +
                "<td>" + costo + "</td><td>" + precio + "</td><td><div class=\"badge badge-light-info\">" + escapeHtml(item.tipo_derivacion) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.relacion_operativa || "SKU normal") + "</div></td>" +
                "<td class=\"pendientes-costo-alerta\">" + item.alertas.map(badge).join("") + "</td><td class=\"pendientes-costo-accion\"><div class=\"mb-2\">" + acciones + "</div><div class=\"d-flex gap-1 flex-wrap\"><a class=\"btn btn-sm btn-light-primary\" href=\"" + escapeHtml(item.url_proveedores) + "\">Proveedores</a><a class=\"btn btn-sm btn-light\" href=\"" + escapeHtml(item.url_listas) + "\">Listas</a>" + costoLink + "</div><span class=\"badge badge-light mt-2\">Aplicacion requiere autorizacion</span></td></tr>";
        }).join("");
    }

    function cargar() {
        var params = {q: document.getElementById("pendientes_costo_buscar").value.trim(), problema: document.getElementById("pendientes_costo_problema").value, limite: document.getElementById("pendientes_costo_limite").value};
        document.getElementById("pendientes_costo_lista").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando fuentes del ERP...</td></tr>";
        return request(params).then(function (response) { if (response.error) { throw new Error(response.mensaje); } var data = response.depurar || {}; items = data.items || []; renderResumen(data.conteos || {}); document.getElementById("pendientes_costo_estado").textContent = "Ventana revisada: " + (data.ventana || 0) + " SKU activos; consulta read-only"; render(); }).catch(function (error) { document.getElementById("pendientes_costo_lista").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-danger py-10\">" + escapeHtml(error.message || String(error)) + "</td></tr>"; });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: conectar filtros y recarga de la bandeja sin habilitar escrituras.
     * Impacto: UI de Calidad / Pendientes de costo; todas las acciones llevan a pantallas existentes.
     */
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("pendientes_costo_recargar").addEventListener("click", cargar);
        ["pendientes_costo_problema", "pendientes_costo_limite"].forEach(function (id) { document.getElementById(id).addEventListener("change", cargar); });
        document.getElementById("pendientes_costo_buscar").addEventListener("input", function () { clearTimeout(timer); timer = setTimeout(cargar, 300); });
        cargar();
    });
})();
