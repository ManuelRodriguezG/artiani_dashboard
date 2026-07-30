"use strict";
(function () {
    var ultimoTimer = null;

    function esc(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : value;
        return div.innerHTML;
    }

    function dinero(value) {
        if (value === null || value === undefined || value === "") {
            return "-";
        }
        var numero = Number(value);
        if (!isFinite(numero)) {
            return "-";
        }
        return "$" + numero.toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 6});
    }

    function numero(value) {
        if (value === null || value === undefined || value === "") {
            return "-";
        }
        var n = Number(value);
        if (!isFinite(n)) {
            return "-";
        }
        return n.toLocaleString("es-MX", {maximumFractionDigits: 6});
    }

    function badge(texto, clase) {
        return "<span class=\"badge " + clase + "\">" + esc(texto) + "</span>";
    }

    function badgeScore(score) {
        var total = score && score.total !== undefined ? Number(score.total) : 0;
        var clase = total >= 75 ? "badge-light-success" : (total >= 50 ? "badge-light-warning" : "badge-light-danger");
        return badge(total + " pts", clase);
    }

    function badgeDictamen(score) {
        var dictamen = score && score.dictamen ? score.dictamen : "revisar";
        var clase = dictamen === "conveniente" ? "badge-light-success" : (dictamen === "riesgo" ? "badge-light-danger" : "badge-light-warning");
        return badge(dictamen, clase);
    }

    function alertasHtml(alertas) {
        if (!alertas || !alertas.length) {
            return badge("sin alertas", "badge-light-success");
        }
        return alertas.map(function (alerta) {
            var nivel = alerta.nivel || "info";
            var clase = nivel === "danger" ? "badge-light-danger" : (nivel === "warning" ? "badge-light-warning" : "badge-light-info");
            return badge(alerta.mensaje || alerta.codigo || "alerta", clase);
        }).join(" ");
    }

    function cardResumen(titulo, valor, icono, clase) {
        return "<div class=\"col-sm-6 col-xl-3\">" +
            "<div class=\"card h-100\"><div class=\"card-body d-flex align-items-center gap-4 py-5\">" +
            "<div class=\"symbol symbol-45px\"><span class=\"symbol-label " + clase + "\"><i class=\"bi " + icono + " fs-2\"></i></span></div>" +
            "<div><div class=\"text-muted fs-7\">" + esc(titulo) + "</div><div class=\"fw-bold fs-2\">" + esc(valor) + "</div></div>" +
            "</div></div></div>";
    }

    function buscar() {
        var termino = document.getElementById("abastecimiento_termino").value.trim();
        var resultados = document.getElementById("abastecimiento_resultados");
        var resumen = document.getElementById("abastecimiento_resumen");
        var error = document.getElementById("abastecimiento_error");
        error.classList.add("d-none");
        error.textContent = "";

        if (termino.length < 2) {
            resultados.innerHTML = "";
            resumen.innerHTML = "<div class=\"col-12 text-muted\">Escribe al menos dos caracteres para buscar.</div>";
            return;
        }

        resumen.innerHTML = "<div class=\"col-12 text-muted\">Consultando alternativas...</div>";
        resultados.innerHTML = "";

        var params = new URLSearchParams();
        params.set("termino", termino);
        params.set("limite", document.getElementById("abastecimiento_limite").value || "80");
        params.set("solo_multiples", document.getElementById("abastecimiento_solo_multiples").checked ? "1" : "0");

        fetch("/proveedor/abastecimiento_comparar_sku_erp?" + params.toString(), {credentials: "same-origin"})
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible consultar el comparativo.");
                }
                render(response.depurar || {});
            })
            .catch(function (e) {
                resumen.innerHTML = "";
                resultados.innerHTML = "";
                error.textContent = e.message;
                error.classList.remove("d-none");
            });
    }

    function render(data) {
        var resumen = data.resumen || {};
        var grupos = data.grupos || [];
        document.getElementById("abastecimiento_resumen").innerHTML =
            cardResumen("SKUs", resumen.skus || 0, "bi-box", "bg-light-primary") +
            cardResumen("Opciones", resumen.opciones || 0, "bi-truck", "bg-light-info") +
            cardResumen("Con multiples", resumen.multiples_proveedores || 0, "bi-diagram-3", "bg-light-warning") +
            cardResumen("Modo", data.sin_escrituras ? "read-only" : "revisar", "bi-shield-check", "bg-light-success");

        document.getElementById("abastecimiento_resultados").innerHTML = grupos.map(grupoHtml).join("") ||
            "<div class=\"card\"><div class=\"card-body text-center text-muted py-10\">Sin alternativas encontradas.</div></div>";
    }

    function grupoHtml(grupo) {
        var opciones = grupo.opciones || [];
        var resumen = grupo.resumen || {};
        return "<div class=\"card\">" +
            "<div class=\"card-header border-0 pt-5 align-items-start flex-wrap gap-3\">" +
            "<div class=\"card-title\"><div>" +
            "<h3 class=\"fw-bold mb-1\">" + esc(grupo.sku || "") + " - " + esc(grupo.nombre || "") + "</h3>" +
            "<span class=\"text-muted fs-7\">Unidad base: " + esc(grupo.unidad_base || "-") + " | Costo referencia: " + dinero(grupo.costo_referencia) + "</span>" +
            "</div></div>" +
            "<div class=\"card-toolbar d-flex gap-2 flex-wrap\">" +
            badge((resumen.proveedores_activos || 0) + " proveedores", "badge-light-primary") +
            badge("mejor " + dinero(resumen.mejor_costo_comparable), "badge-light-success") +
            "</div>" +
            "</div>" +
            "<div class=\"card-body pt-3\">" +
            "<div class=\"table-responsive\">" +
            "<table class=\"table align-middle table-row-dashed gy-4 mb-0\">" +
            "<thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\">" +
            "<th>Proveedor</th><th>Costo</th><th>Unidad/factor</th><th>Compra</th><th>Score</th><th>Alertas</th>" +
            "</tr></thead><tbody>" + opciones.map(opcionHtml).join("") + "</tbody></table>" +
            "</div></div></div>";
    }

    function opcionHtml(opcion) {
        var recomendado = opcion.es_recomendado ? badge("sugerido", "badge-light-success") + " " : "";
        var preferido = Number(opcion.es_preferido || 0) === 1 ? badge("preferido", "badge-light-primary") + " " : "";
        var diferencia = opcion.diferencia_vs_mejor !== null && Number(opcion.diferencia_vs_mejor) > 0
            ? "<div class=\"text-muted fs-8\">+" + dinero(opcion.diferencia_vs_mejor) + " vs mejor</div>"
            : "";
        return "<tr>" +
            "<td><div class=\"fw-bold\">" + recomendado + preferido + esc(opcion.proveedor || "") + "</div>" +
            "<div class=\"text-muted fs-8\">SKU proveedor: " + esc(opcion.sku_proveedor || "-") + "</div>" +
            (opcion.descripcion_proveedor ? "<div class=\"text-muted fs-8\">" + esc(opcion.descripcion_proveedor) + "</div>" : "") +
            "</td>" +
            "<td><div class=\"fw-bold\">" + dinero(opcion.costo_comparable) + "</div>" +
            "<div class=\"text-muted fs-8\">Origen: " + dinero(opcion.costo_origen) + " " + esc(opcion.moneda || "MXN") + "</div>" +
            "<div class=\"text-muted fs-8\">" + esc(opcion.fuente_costo || "") + "</div>" + diferencia + "</td>" +
            "<td><div>" + esc(opcion.unidad_compra || "-") + "</div><div class=\"text-muted fs-8\">Factor " + numero(opcion.factor_conversion) + "</div></td>" +
            "<td><div>Minimo " + numero(opcion.cantidad_minima) + "</div><div class=\"text-muted fs-8\">Entrega " + numero(opcion.dias_entrega) + " dias</div><div class=\"text-muted fs-8\">Existencia prov. " + numero(opcion.existencia_reportada) + "</div></td>" +
            "<td><div class=\"d-flex gap-2 flex-wrap\">" + badgeScore(opcion.score) + badgeDictamen(opcion.score) + "</div></td>" +
            "<td><div class=\"d-flex gap-2 flex-wrap\">" + alertasHtml(opcion.alertas || []) + "</div></td>" +
            "</tr>";
    }

    function bind() {
        document.getElementById("abastecimiento_buscar").addEventListener("click", buscar);
        document.getElementById("abastecimiento_termino").addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                buscar();
                return;
            }
            clearTimeout(ultimoTimer);
            ultimoTimer = setTimeout(buscar, 450);
        });
        document.getElementById("abastecimiento_solo_multiples").addEventListener("change", buscar);
        document.getElementById("abastecimiento_limite").addEventListener("change", buscar);
        var params = new URLSearchParams(window.location.search || "");
        var terminoInicial = params.get("q") || params.get("termino") || "";
        if (terminoInicial) {
            document.getElementById("abastecimiento_termino").value = terminoInicial;
            buscar();
        }
    }

    document.addEventListener("DOMContentLoaded", bind);
})();
