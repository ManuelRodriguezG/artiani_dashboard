"use strict";
(function () {
    var permisos = {crear: false, editar: false};
    var timerBusqueda = null;

    function esc(value) {
        var d = document.createElement("div");
        d.textContent = value == null ? "" : value;
        return d.innerHTML;
    }

    function money(value) {
        return "$" + Number(value || 0).toFixed(2);
    }

    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: new URLSearchParams(data),
            credentials: "same-origin"
        }).then(function (r) { return r.json(); });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: listar y operar revisiones de Sugerido de compra sin mezclar con solicitudes normales.
     * Impacto: Compras/Sugerido; acciones visibles segun permisos y estatus.
     */
    function cargarCatalogos() {
        return fetch("/compra/sugeridos_catalogos_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                var proveedores = r.depurar && Array.isArray(r.depurar.proveedores) ? r.depurar.proveedores : [];
                document.getElementById("sugeridos_proveedor").innerHTML = "<option value=\"\">Todos</option>" + proveedores.map(function (x) {
                    return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
                }).join("");
            });
    }

    function filtros() {
        return {
            q: document.getElementById("sugeridos_buscar").value.trim(),
            estatus: document.getElementById("sugeridos_estatus").value,
            id_proveedor: document.getElementById("sugeridos_proveedor").value
        };
    }

    function cargar() {
        fetch("/compra/sugeridos_listar_erp?" + new URLSearchParams(filtros()), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var schemaPendiente = !!(r.depurar && Number(r.depurar.schema_pendiente || 0) === 1);
                document.getElementById("sugeridos_alerta_schema").classList.toggle("d-none", !schemaPendiente);
                var rows = r.depurar && Array.isArray(r.depurar.items) ? r.depurar.items : [];
                document.getElementById("sugeridos_body").innerHTML = rows.map(renderRow).join("") ||
                    "<tr><td colspan=\"9\" class=\"text-center text-muted py-8\">Sin sugeridos de compra</td></tr>";
            })
            .catch(function (e) {
                document.getElementById("sugeridos_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-center text-danger py-8\">" + esc(e.message || "No se pudo cargar") + "</td></tr>";
            });
    }

    function renderRow(x) {
        var editable = permisos.editar && x.estatus === "borrador";
        var puedeGenerar = permisos.crear && (x.estatus === "borrador" || x.estatus === "lista") && !x.id_solicitud_generada;
        var solicitud = x.id_solicitud_generada
            ? "<a class=\"badge badge-light-success\" href=\"/compra/mostrar_solicitud/" + esc(x.id_solicitud_generada) + "\">" + esc(x.folio_solicitud || "Solicitud") + "</a>"
            : "<span class=\"badge badge-light-warning\">Sin solicitud</span>";
        var acciones = "<a class=\"btn btn-sm btn-light-success\" href=\"/compra/ver_sugerido_compra/" + esc(x.id_sugerido_compra) + "\"><i class=\"bi bi-eye me-1\"></i>Ver</a>";
        if (editable) {
            acciones += " <a class=\"btn btn-sm btn-light-info\" href=\"/compra/sugerido_compra/" + esc(x.id_sugerido_compra) + "\"><i class=\"bi bi-pencil-square me-1\"></i>Editar</a>";
        }
        if (permisos.crear) {
            acciones += " <button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-sugerido-duplicar=\"" + esc(x.id_sugerido_compra) + "\"><i class=\"bi bi-copy me-1\"></i>Duplicar</button>";
        }
        if (puedeGenerar) {
            acciones += " <button type=\"button\" class=\"btn btn-sm btn-primary\" data-sugerido-generar=\"" + esc(x.id_sugerido_compra) + "\"><i class=\"bi bi-file-earmark-plus me-1\"></i>Solicitud</button>";
        }
        return "<tr>" +
            "<td class=\"fw-bold\">" + esc(x.folio || "-") + "</td>" +
            "<td>" + esc(x.proveedor || "-") + "</td>" +
            "<td>" + esc(x.fecha_registro || "-") + "</td>" +
            "<td class=\"text-end\">" + Number(x.total_partidas || 0) + "</td>" +
            "<td class=\"text-end\">" + Number(x.total_unidades || 0).toFixed(6) + "</td>" +
            "<td class=\"text-end fw-bold\">" + money(x.total_estimado) + "</td>" +
            "<td>" + solicitud + "</td>" +
            "<td><span class=\"badge badge-light\">" + esc(x.estatus || "-") + "</span></td>" +
            "<td class=\"text-end text-nowrap\">" + acciones + "</td>" +
            "</tr>";
    }

    function duplicar(id) {
        post("/compra/sugerido_duplicar_erp", {id_sugerido_compra: id}).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            var omitidos = r.depurar && Number(r.depurar.omitidos_por_relacion_inactiva || 0) > 0
                ? " Se omitieron " + Number(r.depurar.omitidos_por_relacion_inactiva || 0) + " relacion(es) inactivas."
                : "";
            Swal.fire({text: r.mensaje + "." + omitidos, icon: "success", confirmButtonText: "Abrir"}).then(function () {
                window.location.href = "/compra/sugerido_compra/" + r.depurar.id_sugerido_compra;
            });
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo duplicar", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function generarSolicitud(id) {
        post("/compra/sugerido_generar_solicitud_erp", {id_sugerido_compra: id}).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            window.location.href = "/compra/editar_solicitud/" + r.depurar.id_solicitud;
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo generar solicitud", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        permisos.crear = Number(document.getElementById("sugeridos_permiso_crear").value || 0) === 1;
        permisos.editar = Number(document.getElementById("sugeridos_permiso_editar").value || 0) === 1;
        document.getElementById("sugeridos_buscar").addEventListener("input", function () {
            clearTimeout(timerBusqueda);
            timerBusqueda = setTimeout(cargar, 250);
        });
        ["sugeridos_estatus", "sugeridos_proveedor"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", cargar);
        });
        document.getElementById("sugeridos_body").addEventListener("click", function (e) {
            var duplicarId = e.target.closest("[data-sugerido-duplicar]");
            var generarId = e.target.closest("[data-sugerido-generar]");
            if (duplicarId) {
                duplicar(duplicarId.getAttribute("data-sugerido-duplicar"));
            } else if (generarId) {
                generarSolicitud(generarId.getAttribute("data-sugerido-generar"));
            }
        });
        cargarCatalogos().then(cargar);
    });
})();
