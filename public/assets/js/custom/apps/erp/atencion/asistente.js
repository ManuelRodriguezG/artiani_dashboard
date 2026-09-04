/**
 * IA: Codex GPT-5 | Fecha: 2026-09-04
 * Proposito: operar el asesor comercial interno para prospectos.
 * Impacto: UI CRM/Prospectos; genera sugerencias editables sin enviar mensajes ni confirmar stock/precios.
 */
(function () {
    const ENDPOINT_CATALOGOS = "/atencion/catalogos_erp";
    const ENDPOINT_RESPUESTA = "/atencion/respuesta_sugerida_erp";

    document.addEventListener("DOMContentLoaded", function () {
        enlazarEventos();
        cargarCatalogos();
    });

    function enlazarEventos() {
        const generar = document.getElementById("atencion_generar");
        const limpiar = document.getElementById("atencion_limpiar");
        const copiar = document.getElementById("atencion_copiar");

        if (generar) {
            generar.addEventListener("click", generarRespuesta);
        }
        if (limpiar) {
            limpiar.addEventListener("click", limpiarFormulario);
        }
        if (copiar) {
            copiar.addEventListener("click", copiarRespuesta);
        }
    }

    function cargarCatalogos() {
        consultarJson(ENDPOINT_CATALOGOS).then(function (respuesta) {
            const datos = respuesta.depurar || {};
            llenarSelect("atencion_canal", datos.canales || [], "whatsapp");
            llenarSelectObjeto("atencion_intencion", datos.intenciones || {}, "categoria");
            llenarSelectObjeto("atencion_categoria", nombresCategorias(datos.categorias || {}), "general");
            renderLista("atencion_recordatorios", datos.reglas || []);
        }).catch(function (error) {
            setText("atencion_estado", error.message);
        });
    }

    function generarRespuesta() {
        const params = new URLSearchParams();
        params.set("canal", valor("atencion_canal"));
        params.set("intencion", valor("atencion_intencion"));
        params.set("categoria", valor("atencion_categoria"));
        params.set("producto", valor("atencion_producto"));
        params.set("mensaje_cliente", valor("atencion_mensaje_cliente"));
        params.set("url_catalogo", valor("atencion_url_catalogo"));
        setText("atencion_estado", "Generando sugerencia...");

        consultarJson(ENDPOINT_RESPUESTA + "?" + params.toString()).then(function (respuesta) {
            const datos = respuesta.depurar || {};
            setValue("atencion_respuesta", datos.respuesta || "");
            renderLista("atencion_preguntas", datos.preguntas || []);
            renderLista("atencion_recordatorios", datos.recordatorios || []);
            renderVariantes(datos.variantes || {});
            setText("atencion_estado", "Sugerencia lista para revisar y editar");
        }).catch(function (error) {
            setText("atencion_estado", error.message);
        });
    }

    function copiarRespuesta() {
        const texto = valor("atencion_respuesta");
        if (!texto) {
            return;
        }
        navigator.clipboard.writeText(texto).then(function () {
            setText("atencion_estado", "Respuesta copiada");
        }).catch(function () {
            setText("atencion_estado", "No fue posible copiar automaticamente");
        });
    }

    function limpiarFormulario() {
        setValue("atencion_producto", "");
        setValue("atencion_mensaje_cliente", "");
        setValue("atencion_url_catalogo", "");
        setValue("atencion_respuesta", "");
        renderLista("atencion_preguntas", []);
        renderVariantes({});
        setText("atencion_estado", "Formulario limpio");
    }

    function consultarJson(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible consultar el asistente");
            }
            return respuesta.json();
        });
    }

    function llenarSelect(id, valores, seleccionado) {
        const select = document.getElementById(id);
        if (!select) {
            return;
        }
        select.innerHTML = valores.map(function (valor) {
            return '<option value="' + escapeHtml(valor) + '"' + (valor === seleccionado ? " selected" : "") + '>' + escapeHtml(etiqueta(valor)) + '</option>';
        }).join("");
    }

    function llenarSelectObjeto(id, valores, seleccionado) {
        const select = document.getElementById(id);
        if (!select) {
            return;
        }
        select.innerHTML = Object.keys(valores).map(function (clave) {
            return '<option value="' + escapeHtml(clave) + '"' + (clave === seleccionado ? " selected" : "") + '>' + escapeHtml(valores[clave]) + '</option>';
        }).join("");
    }

    function nombresCategorias(categorias) {
        const nombres = {};
        Object.keys(categorias).forEach(function (clave) {
            nombres[clave] = categorias[clave].nombre || etiqueta(clave);
        });
        return nombres;
    }

    function renderLista(id, items) {
        const contenedor = document.getElementById(id);
        if (!contenedor) {
            return;
        }
        if (!items.length) {
            contenedor.innerHTML = '<span class="text-muted">Sin elementos</span>';
            return;
        }
        contenedor.innerHTML = items.map(function (item) {
            return '<div class="d-flex gap-2"><i class="bi bi-check2 text-primary"></i><span>' + escapeHtml(item) + '</span></div>';
        }).join("");
    }

    function renderVariantes(variantes) {
        const contenedor = document.getElementById("atencion_variantes");
        if (!contenedor) {
            return;
        }
        const claves = Object.keys(variantes);
        if (!claves.length) {
            contenedor.innerHTML = '<div class="col-12 text-muted">Sin variantes generadas</div>';
            return;
        }
        contenedor.innerHTML = claves.map(function (clave) {
            return '<div class="col-md-4"><button type="button" class="btn btn-light w-100 h-100 text-start atencion-variante" data-texto="' + escapeHtml(variantes[clave]) + '"><span class="fw-bold d-block mb-2">' + escapeHtml(etiqueta(clave)) + '</span><span class="fs-8 text-gray-700">' + escapeHtml(variantes[clave]) + '</span></button></div>';
        }).join("");
        contenedor.querySelectorAll(".atencion-variante").forEach(function (boton) {
            boton.addEventListener("click", function () {
                setValue("atencion_respuesta", boton.getAttribute("data-texto") || "");
                setText("atencion_estado", "Variante cargada para editar");
            });
        });
    }

    function etiqueta(valor) {
        return String(valor || "").replace(/_/g, " ").replace(/\b\w/g, function (letra) {
            return letra.toUpperCase();
        });
    }

    function valor(id) {
        const elemento = document.getElementById(id);
        return elemento ? String(elemento.value || "").trim() : "";
    }

    function setValue(id, valorNuevo) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.value = valorNuevo;
        }
    }

    function setText(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = String(texto || "");
        }
    }

    function escapeHtml(valor) {
        return String(valor == null ? "" : valor)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
