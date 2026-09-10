"use strict";

(function () {
    var productos = [];
    var categorias = [];
    var marcas = [];
    var timer = null;
    var permisos = window.CATALOGO_PERMISOS || {};

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function selectedIds(csv) {
        return String(csv || "").split(",").map(function (id) {
            return parseInt(id, 10);
        }).filter(function (id) {
            return id > 0;
        });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-09
     * Proposito: construir opciones de marca/categoria con busqueda tipo Select2 cuando el plugin esta disponible.
     * Impacto: Catalogo ERP; mejora captura rapida en listados grandes sin cambiar contratos de BD.
     * Contrato: recibe valores seleccionados como enteros o arreglo de enteros y devuelve HTML seguro.
     */
    function opciones(items, idKey, labelFn, selected, emptyText) {
        var seleccionados = Array.isArray(selected) ? selected.map(String) : [String(selected || "")];
        var html = emptyText == null ? "" : "<option value=\"\">" + escapeHtml(emptyText) + "</option>";
        html += items.map(function (item) {
            var id = String(item[idKey]);
            var sel = seleccionados.indexOf(id) !== -1 ? " selected" : "";
            return "<option value=\"" + escapeAttr(id) + "\"" + sel + ">" + escapeHtml(labelFn(item)) + "</option>";
        }).join("");
        return html;
    }

    function renderFaltantes(item) {
        var badges = [];
        if (!item.id_marca_erp) {
            badges.push("<span class=\"badge badge-light-warning\">Sin marca</span>");
        }
        if (!item.id_categoria_principal) {
            badges.push("<span class=\"badge badge-light-danger\">Sin principal</span>");
        }
        if (!item.ids_categorias_secundarias) {
            badges.push("<span class=\"badge badge-light\">Sin secundarias</span>");
        }
        return badges.join(" ") || "<span class=\"badge badge-light-success\">Completo</span>";
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-09
     * Proposito: renderizar tabla editable de clasificacion con guardado por fila.
     * Impacto: Catalogo ERP; evita abrir el modal completo para cambios editoriales de marca/categoria.
     * Contrato: usa productos agrupados por maestro, marcas y categorias provistas por /catalogoerp/clasificacion_rapida_datos.
     */
    function render() {
        var lista = document.getElementById("clasificacion_lista");
        document.getElementById("clasificacion_total").textContent = productos.length + " productos";
        if (!productos.length) {
            lista.innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">No hay productos con estos filtros</td></tr>";
            return;
        }
        lista.innerHTML = productos.map(function (item) {
            var secundarias = selectedIds(item.ids_categorias_secundarias);
            var disabled = permisos.editar ? "" : " disabled";
            return "<tr data-producto=\"" + escapeAttr(item.id_producto_erp) + "\">" +
                "<td class=\"catalogo-rapido-skus\"><div class=\"fw-bold\">" + escapeHtml(item.codigo_producto || "Sin codigo") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.skus || "Sin SKU") + "</div><span class=\"badge badge-light\">" + escapeHtml(item.total_skus || 0) + " SKU agrupados</span></td>" +
                "<td class=\"catalogo-rapido-producto\"><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_producto || "") + " | " + escapeHtml(item.estatus || "") + "</div></td>" +
                "<td><select class=\"form-select form-select-solid catalogo-rapido-select\" data-marca" + disabled + ">" + opciones(marcas, "id_marca_erp", function (marca) { return marca.nombre; }, item.id_marca_erp, "Sin marca") + "</select></td>" +
                "<td><select class=\"form-select form-select-solid catalogo-rapido-select\" data-principal" + disabled + ">" + opciones(categorias, "id_categoria_erp", function (categoria) { return categoria.ruta || categoria.nombre; }, item.id_categoria_principal, "Sin categoria") + "</select></td>" +
                "<td><select class=\"form-select form-select-solid catalogo-rapido-secundarias\" data-secundarias multiple" + disabled + ">" + opciones(categorias, "id_categoria_erp", function (categoria) { return categoria.ruta || categoria.nombre; }, secundarias, null) + "</select></td>" +
                "<td data-faltantes>" + renderFaltantes(item) + "</td>" +
                "<td class=\"text-end\">" + (permisos.editar ? "<button class=\"btn btn-sm btn-light-success\" type=\"button\" data-guardar><i class=\"bi bi-check2\"></i> Guardar</button>" : "<span class=\"badge badge-light\">Solo lectura</span>") + "</td>" +
                "</tr>";
        }).join("");
        activarSelects();
    }

    function activarSelects() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        jQuery(".catalogo-rapido-select").select2({width: "100%", placeholder: "Seleccionar"});
        jQuery(".catalogo-rapido-secundarias").select2({width: "100%", placeholder: "Categorias secundarias"});
    }

    function cargar() {
        var params = new URLSearchParams({
            q: document.getElementById("clasificacion_buscar").value.trim(),
            estatus: document.getElementById("clasificacion_estatus").value,
            faltante_marca: document.getElementById("clasificacion_sin_marca").checked ? "1" : "0",
            faltante_principal: document.getElementById("clasificacion_sin_principal").checked ? "1" : "0",
            faltante_secundarias: document.getElementById("clasificacion_sin_secundarias").checked ? "1" : "0",
            limite: document.getElementById("clasificacion_limite").value
        });
        document.getElementById("clasificacion_lista").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Cargando clasificacion...</td></tr>";
        return request("/catalogoerp/clasificacion_rapida_datos?" + params.toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            productos = data.productos || [];
            categorias = data.categorias || [];
            marcas = data.marcas || [];
            render();
        }).catch(function (error) {
            document.getElementById("clasificacion_lista").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-danger py-10\">" + escapeHtml(error.message || String(error)) + "</td></tr>";
        });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-09
     * Proposito: guardar una fila de marca/categorias y actualizar visualmente sus faltantes.
     * Impacto: Catalogo ERP; persiste cambios puntuales con auditoria del backend.
     * Contrato: envia IDs actuales de selects; la categoria principal se elimina de secundarias antes de guardar.
     */
    function guardarFila(button) {
        var row = button.closest("[data-producto]");
        var principal = row.querySelector("[data-principal]").value;
        var secundarias = Array.prototype.slice.call(row.querySelector("[data-secundarias]").selectedOptions).map(function (option) {
            return option.value;
        }).filter(function (id) {
            return id && id !== principal;
        });
        row.classList.add("catalogo-rapido-row-saving");
        button.disabled = true;
        request("/catalogoerp/clasificacion_rapida_guardar", {
            id_producto_erp: row.getAttribute("data-producto"),
            id_marca_erp: row.querySelector("[data-marca]").value,
            id_categoria_principal: principal,
            categorias_secundarias: JSON.stringify(secundarias)
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            row.querySelector("[data-faltantes]").innerHTML = renderFaltantes({
                id_marca_erp: row.querySelector("[data-marca]").value,
                id_categoria_principal: principal,
                ids_categorias_secundarias: secundarias.join(",")
            });
            var label = document.createElement("span");
            label.className = "badge badge-light-success ms-1";
            label.textContent = "Guardado";
            row.querySelector("[data-faltantes]").appendChild(label);
        }).catch(function (error) {
            Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
        }).finally(function () {
            row.classList.remove("catalogo-rapido-row-saving");
            button.disabled = false;
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("clasificacion_recargar").addEventListener("click", cargar);
        ["clasificacion_estatus", "clasificacion_limite", "clasificacion_sin_marca", "clasificacion_sin_principal", "clasificacion_sin_secundarias"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", cargar);
        });
        document.getElementById("clasificacion_buscar").addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(cargar, 300);
        });
        document.getElementById("clasificacion_lista").addEventListener("click", function (event) {
            var button = event.target.closest("[data-guardar]");
            if (button) {
                guardarFila(button);
            }
        });
        cargar();
    });
})();
