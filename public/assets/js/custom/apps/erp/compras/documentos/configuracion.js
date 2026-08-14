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
    var camposPlantillaTexto = [
        "titulo_documento",
        "subtitulo_documento"
    ];
    var camposNegocio = [
        "empresa_nombre",
        "empresa_razon_social",
        "empresa_rfc",
        "empresa_contacto",
        "empresa_email",
        "empresa_telefono",
        "empresa_direccion"
    ];

    function esc(value) {
        var d = document.createElement("div");
        d.textContent = value == null ? "" : value;
        return d.innerHTML;
    }

    function request(url, data) {
        var headers = {};
        var body = null;
        if (data) {
            headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
            body = new URLSearchParams(data).toString();
        }
        if (window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: headers,
            body: body,
            credentials: "same-origin"
        }).then(function (r) { return r.json(); });
    }

    function requestArchivo(url, formData) {
        var headers = {};
        if (window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: "POST",
            headers: headers,
            body: formData,
            credentials: "same-origin"
        }).then(function (r) { return r.json(); });
    }

    function cargarDatosNegocio() {
        request("/compra/documentos_datos_negocio_consultar_erp")
            .then(function (r) {
                if (r.error) {
                    mostrarMensaje(r.mensaje || "No fue posible consultar datos del negocio", "warning");
                    return;
                }
                renderDatosNegocio(r.depurar || {});
            })
            .catch(function () {
                mostrarMensaje("Error al consultar datos del negocio", "danger");
            });
    }

    function renderDatosNegocio(datos) {
        camposNegocio.forEach(function (campo) {
            var input = document.getElementById("negocio_" + campo);
            if (input) {
                input.value = datos[campo] || "";
            }
        });
        renderLogo(datos.logo_ruta || "");
    }

    function renderLogo(ruta) {
        var img = document.getElementById("documentos_logo_preview");
        var empty = document.getElementById("documentos_logo_empty");
        if (!img || !empty) {
            return;
        }
        if (ruta) {
            img.src = ruta;
            img.classList.remove("d-none");
            empty.classList.add("d-none");
        } else {
            img.removeAttribute("src");
            img.classList.add("d-none");
            empty.classList.remove("d-none");
        }
    }

    function cargarPlantillas() {
        request("/compra/documentos_plantillas_consultar_erp")
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
        document.getElementById("plantilla_pie_pagina").value = plantillaActual.pie_pagina || "";
        camposPlantillaTexto.forEach(function (campo) {
            var input = document.getElementById("plantilla_" + campo);
            if (input) {
                input.value = plantillaActual[campo] || "";
            }
        });
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
        fd.append("logo_ruta", "");
        fd.append("pie_pagina", document.getElementById("plantilla_pie_pagina").value);
        camposPlantillaTexto.forEach(function (campo) {
            var input = document.getElementById("plantilla_" + campo);
            fd.append(campo, input ? input.value : "");
        });
        camposBool.forEach(function (campo) {
            fd.append(campo, document.getElementById(campo).checked ? "1" : "0");
        });

        requestArchivo("/compra/documentos_plantillas_guardar_erp", fd)
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

    function guardarDatosNegocio() {
        var datos = {};
        camposNegocio.forEach(function (campo) {
            var input = document.getElementById("negocio_" + campo);
            datos[campo] = input ? input.value : "";
        });
        request("/compra/documentos_datos_negocio_guardar_erp", datos)
            .then(function (r) {
                mostrarMensaje(r.mensaje || "Datos guardados", r.error ? "warning" : "success");
                if (!r.error) {
                    renderDatosNegocio((r.depurar && r.depurar.datos_negocio) || {});
                    cargarPlantillas();
                }
            })
            .catch(function () {
                mostrarMensaje("Error al guardar datos del negocio", "danger");
            });
    }

    function subirLogo() {
        var input = document.getElementById("documentos_logo_archivo");
        if (!input || !input.files || !input.files.length) {
            mostrarMensaje("Selecciona un archivo de logo", "warning");
            return;
        }
        var fd = new FormData();
        fd.append("logo", input.files[0]);
        fd.append("motivo", "Logo compartido para documentos de Compras");
        requestArchivo("/compra/documentos_logo_subir_erp", fd)
            .then(function (r) {
                mostrarMensaje(r.mensaje || "Logo guardado", r.error ? "warning" : "success");
                if (!r.error) {
                    input.value = "";
                    cargarDatosNegocio();
                    cargarPlantillas();
                }
            })
            .catch(function () {
                mostrarMensaje("Error al subir logo", "danger");
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
        document.getElementById("compras_documentos_negocio_guardar").addEventListener("click", guardarDatosNegocio);
        document.getElementById("documentos_logo_subir").addEventListener("click", subirLogo);
        cargarDatosNegocio();
        cargarPlantillas();
    });
})();
