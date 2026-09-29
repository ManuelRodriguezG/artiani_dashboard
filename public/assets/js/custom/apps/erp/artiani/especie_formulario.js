/**
 * IA: Codex GPT-5 | Fecha: 2026-09-29
 * Proposito: preparar captura de nueva ficha Artiani en modo borrador visual.
 * Impacto: UI Conocimiento; permite definir campos y revisar estructura antes de activar guardado real.
 * Contrato: no envia datos ni escribe BD.
 */
(function () {
    const ENDPOINT_CATALOGOS = "/artiani/catalogos_erp";
    let catalogos = { grupos: {}, dificultades: {} };

    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos();
        enlazarEventos();
    });

    function cargarCatalogos() {
        consultarJson(ENDPOINT_CATALOGOS).then(function (respuesta) {
            catalogos = respuesta.depurar || catalogos;
            llenarSelect("artiani_form_grupo", catalogos.grupos || {});
            llenarSelect("artiani_form_dificultad", catalogos.dificultades || {});
        }).catch(function (error) {
            setHtml("artiani_form_preview", '<div class="text-danger">' + escapeHtml(error.message) + '</div>');
        });
    }

    function enlazarEventos() {
        on("artiani_form_previsualizar", "click", previsualizar);
        on("artiani_form_limpiar", "click", limpiar);
    }

    function previsualizar() {
        const nombre = valor("artiani_form_nombre") || "Ficha sin nombre";
        const grupo = valor("artiani_form_grupo");
        const dificultad = valor("artiani_form_dificultad");
        setHtml("artiani_form_preview",
            '<div class="mb-4">' +
                '<div class="fw-bold fs-5">' + escapeHtml(nombre) + '</div>' +
                '<div class="d-flex gap-2 mt-2">' +
                    '<span class="badge badge-light-primary">' + escapeHtml(nombreCatalogo(catalogos.grupos, grupo)) + '</span>' +
                    '<span class="badge badge-light">' + escapeHtml(nombreCatalogo(catalogos.dificultades, dificultad)) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="mb-4 text-gray-700">' + escapeHtml(valor("artiani_form_resumen") || "Sin resumen") + '</div>' +
            bloque("Habitat", lineas("artiani_form_habitat")) +
            bloque("Alimentacion", lineas("artiani_form_alimentacion")) +
            bloque("Preguntas", lineas("artiani_form_preguntas")) +
            bloque("Alertas", lineas("artiani_form_alertas")) +
            '<div class="mt-4"><div class="fw-semibold mb-2">Productos puente</div><div class="d-flex flex-wrap gap-2">' + productos().map(function (item) {
                return '<span class="badge badge-light-info">' + escapeHtml(item) + '</span>';
            }).join("") + '</div></div>'
        );
    }

    function limpiar() {
        ["artiani_form_nombre", "artiani_form_resumen", "artiani_form_habitat", "artiani_form_alimentacion", "artiani_form_preguntas", "artiani_form_alertas", "artiani_form_productos"].forEach(function (id) {
            setValue(id, "");
        });
        setHtml("artiani_form_preview", '<div class="text-muted">Captura datos y previsualiza la ficha antes de definir el guardado.</div>');
    }

    function bloque(titulo, items) {
        return '<div class="mb-4"><div class="fw-semibold mb-2">' + escapeHtml(titulo) + '</div>' +
            (items.length ? '<ul class="mb-0 ps-4">' + items.map(function (item) { return '<li>' + escapeHtml(item) + '</li>'; }).join("") + '</ul>' : '<div class="text-muted">Sin informacion</div>') +
        '</div>';
    }

    function lineas(id) {
        return valor(id).split(/\n+/).map(function (item) { return item.trim(); }).filter(Boolean);
    }

    function productos() {
        return valor("artiani_form_productos").split(",").map(function (item) { return item.trim(); }).filter(Boolean);
    }

    function consultarJson(url) {
        return fetch(url, { method: "GET", credentials: "same-origin", headers: { "Accept": "application/json" } })
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error("No fue posible consultar Artiani");
                }
                return respuesta.json();
            });
    }

    function llenarSelect(id, valores) {
        const select = document.getElementById(id);
        if (!select) {
            return;
        }
        select.innerHTML = Object.keys(valores).map(function (clave) {
            return '<option value="' + escapeHtml(clave) + '">' + escapeHtml(valores[clave]) + '</option>';
        }).join("");
    }

    function nombreCatalogo(catalogo, clave) {
        return (catalogo || {})[clave] || etiqueta(clave);
    }

    function etiqueta(valor) {
        return String(valor || "").replace(/_/g, " ").replace(/\b\w/g, function (letra) {
            return letra.toUpperCase();
        });
    }

    function on(id, evento, callback) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.addEventListener(evento, callback);
        }
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

    function setHtml(id, html) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.innerHTML = html;
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
