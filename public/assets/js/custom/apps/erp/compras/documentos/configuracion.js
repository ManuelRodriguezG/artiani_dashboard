"use strict";

/**
 * IA: Codex GPT-5 | Fecha: 2026-07-28
 * Proposito: administrar plantillas imprimibles de Compras desde una vista operativa.
 * Impacto: Solicitudes/Ordenes; permite configurar visibilidad de costos, SKUs, logo y observaciones.
 */
(function () {
    var plantillas = [];
    var plantillaActual = null;
    var camposBool = [
        "mostrar_logo",
        "mostrar_costos",
        "mostrar_impuestos",
        "mostrar_totales",
        "mostrar_sku_proveedor",
        "mostrar_sku_erp",
        "mostrar_nombre_proveedor",
        "mostrar_nombre_erp",
        "mostrar_observaciones_publicas",
        "mostrar_observaciones_internas"
    ];

    function esc(value) {
        var d = document.createElement("div");
        d.textContent = value == null ? "" : value;
        return d.innerHTML;
    }

    function cargarPlantillas() {
        fetch("/compra/documentos_plantillas_consultar_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) {
                    mostrarMensaje(r.mensaje || "No fue posible consultar plantillas", "warning");
                    return;
                }
                plantillas = r.depurar || [];
                renderPlantillas();
                if (plantillas.length) {
                    seleccionarPlantilla(plantillas[0].codigo);
                }
            })
            .catch(function () {
                mostrarMensaje("Error al consultar plantillas", "danger");
            });
    }

    function renderPlantillas() {
        var contenedor = document.getElementById("compras_documentos_plantillas");
        contenedor.innerHTML = plantillas.map(function (p) {
            var activa = plantillaActual && plantillaActual.codigo === p.codigo ? " active" : "";
            var etiqueta = p.audiencia === "proveedor" ? "Proveedor" : "Interna";
            return "<button type=\"button\" class=\"btn btn-light text-start plantilla-doc" + activa +
                "\" data-codigo=\"" + esc(p.codigo) + "\">" +
                "<span class=\"fw-bold d-block\">" + esc(p.nombre) + "</span>" +
                "<span class=\"text-muted fs-8\">" + esc(p.tipo_documento) + " · " + esc(etiqueta) + "</span>" +
                "</button>";
        }).join("") || "<div class=\"text-muted\">Sin plantillas configuradas</div>";

        contenedor.querySelectorAll(".plantilla-doc").forEach(function (btn) {
            btn.addEventListener("click", function () {
                seleccionarPlantilla(btn.getAttribute("data-codigo"));
            });
        });
    }

    function seleccionarPlantilla(codigo) {
        plantillaActual = plantillas.find(function (p) { return p.codigo === codigo; }) || null;
        if (!plantillaActual) {
            return;
        }
        document.getElementById("compras_documentos_form").classList.remove("d-none");
        document.getElementById("compras_documentos_empty").classList.add("d-none");
        document.getElementById("compras_documentos_titulo").textContent = plantillaActual.nombre;
        document.getElementById("plantilla_id").value = plantillaActual.id_plantilla_documento || "";
        document.getElementById("plantilla_codigo").value = plantillaActual.codigo || "";
        document.getElementById("plantilla_nombre").value = plantillaActual.nombre || "";
        document.getElementById("plantilla_descripcion").value = plantillaActual.descripcion || "";
        document.getElementById("plantilla_logo_ruta").value = plantillaActual.logo_ruta || "";
        document.getElementById("plantilla_pie_pagina").value = plantillaActual.pie_pagina || "";
        camposBool.forEach(function (campo) {
            document.getElementById(campo).checked = Number(plantillaActual[campo] || 0) === 1;
        });
        renderPlantillas();
    }

    function guardarPlantilla() {
        if (!plantillaActual) {
            mostrarMensaje("Selecciona una plantilla", "warning");
            return;
        }
        var fd = new FormData();
        fd.append("id_plantilla_documento", document.getElementById("plantilla_id").value);
        fd.append("codigo", document.getElementById("plantilla_codigo").value);
        fd.append("nombre", document.getElementById("plantilla_nombre").value);
        fd.append("descripcion", document.getElementById("plantilla_descripcion").value);
        fd.append("logo_ruta", document.getElementById("plantilla_logo_ruta").value);
        fd.append("pie_pagina", document.getElementById("plantilla_pie_pagina").value);
        camposBool.forEach(function (campo) {
            fd.append(campo, document.getElementById(campo).checked ? "1" : "0");
        });

        fetch("/compra/documentos_plantillas_guardar_erp", {
            method: "POST",
            credentials: "same-origin",
            body: fd
        })
            .then(function (r) { return r.json(); })
            .then(function (r) {
                mostrarMensaje(r.mensaje || "Configuracion guardada", r.error ? "warning" : "success");
                if (!r.error) {
                    cargarPlantillas();
                }
            })
            .catch(function () {
                mostrarMensaje("Error al guardar la configuracion", "danger");
            });
    }

    function mostrarMensaje(mensaje, tipo) {
        if (window.Swal) {
            Swal.fire({
                text: mensaje,
                icon: tipo === "success" ? "success" : "warning",
                buttonsStyling: false,
                confirmButtonText: "Entendido",
                customClass: {confirmButton: "btn btn-primary"}
            });
            return;
        }
        alert(mensaje);
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("compras_documentos_guardar").addEventListener("click", guardarPlantilla);
        cargarPlantillas();
    });
})();
