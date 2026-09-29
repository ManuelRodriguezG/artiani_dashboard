/**
 * IA: Codex GPT-5 | Fecha: 2026-09-29
 * Proposito: renderizar una ficha independiente de especie Artiani.
 * Impacto: UI Conocimiento/Capacitacion; muestra detalle completo sin cargar el listado.
 * Contrato: read-only; consulta por slug desde `/artiani/especie_consultar_erp`.
 */
(function () {
    const ENDPOINT_CATALOGOS = "/artiani/catalogos_erp";
    const ENDPOINT_CONSULTAR = "/artiani/especie_consultar_erp";
    let catalogos = { grupos: {}, dificultades: {} };

    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos().then(cargarFicha);
    });

    function cargarCatalogos() {
        return consultarJson(ENDPOINT_CATALOGOS).then(function (respuesta) {
            catalogos = respuesta.depurar || catalogos;
        }).catch(function () {});
    }

    function cargarFicha() {
        const slug = valor("artiani_ficha_slug");
        if (!slug) {
            renderError("No se recibio la ficha solicitada.");
            return;
        }
        consultarJson(ENDPOINT_CONSULTAR + "?slug=" + encodeURIComponent(slug)).then(function (respuesta) {
            const datos = respuesta.depurar || {};
            renderDetalle(datos.especie || {}, datos.productos_relacionados_pendientes || []);
        }).catch(function (error) {
            renderError(error.message);
        });
    }

    function renderDetalle(especie, productosPendientes) {
        if (!especie.slug) {
            renderError("No se encontro la especie solicitada.");
            return;
        }
        setText("artiani_ficha_titulo", especie.nombre || "Ficha de especie");
        setText("artiani_ficha_subtitulo", especie.resumen || "Detalle operativo Artiani");
        const contenedor = document.getElementById("artiani_ficha_contenido");
        contenedor.innerHTML =
            '<div class="d-flex flex-stack gap-4 mb-6">' +
                '<div>' +
                    '<h2 class="fs-2 fw-bold mb-2">' + escapeHtml(especie.nombre) + '</h2>' +
                    '<div class="text-gray-700">' + escapeHtml(especie.resumen) + '</div>' +
                '</div>' +
                '<div class="text-end">' +
                    '<span class="badge badge-light-primary d-block mb-2">' + escapeHtml(nombreCatalogo(catalogos.grupos, especie.grupo)) + '</span>' +
                    '<span class="badge badge-light">' + escapeHtml(nombreCatalogo(catalogos.dificultades, especie.dificultad)) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="border border-gray-200 rounded p-4 mb-6"><div class="fw-semibold mb-2">Perfil de cliente</div><div class="text-gray-700">' + escapeHtml(especie.perfil_cliente || "") + '</div></div>' +
            '<div class="row g-4 mb-6">' +
                bloque("Habitat", especie.habitat || [], "bi-house-heart") +
                bloque("Alimentacion", especie.alimentacion || [], "bi-cup-hot") +
                bloque("Cuidados", especie.cuidados || [], "bi-clipboard-check") +
                bloque("Preguntas clave", especie.preguntas_clave || [], "bi-question-circle") +
            '</div>' +
            '<div class="border border-warning rounded p-5 mb-6">' +
                '<h3 class="fs-6 fw-bold mb-3"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Alertas de venta responsable</h3>' +
                renderListaSimple(especie.alertas || [], "warning") +
            '</div>' +
            '<div class="border border-gray-200 rounded p-5">' +
                '<div class="d-flex flex-stack mb-3">' +
                    '<h3 class="fs-6 fw-bold mb-0">Productos relacionados por vincular</h3>' +
                    '<span class="badge badge-light-info">' + productosPendientes.length + ' pendientes</span>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-2">' + productosPendientes.map(function (item) {
                    return '<span class="badge badge-light-info">' + escapeHtml(item.necesidad) + '</span>';
                }).join("") + '</div>' +
            '</div>';
    }

    function bloque(titulo, items, icono) {
        return '<div class="col-md-6">' +
            '<div class="border border-gray-200 rounded p-4 h-100">' +
                '<h3 class="fs-6 fw-bold mb-3"><i class="bi ' + icono + ' text-primary me-2"></i>' + escapeHtml(titulo) + '</h3>' +
                renderListaSimple(items, "primary") +
            '</div>' +
        '</div>';
    }

    function renderListaSimple(items, tipo) {
        if (!items.length) {
            return '<div class="text-muted">Sin informacion</div>';
        }
        return '<div class="d-flex flex-column gap-2">' + items.map(function (item) {
            return '<div class="d-flex gap-2"><i class="bi bi-check2 text-' + tipo + '"></i><span>' + escapeHtml(item) + '</span></div>';
        }).join("") + '</div>';
    }

    function renderError(mensaje) {
        const contenedor = document.getElementById("artiani_ficha_contenido");
        if (contenedor) {
            contenedor.innerHTML = '<div class="text-danger">' + escapeHtml(mensaje) + '</div>';
        }
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

    function nombreCatalogo(catalogo, clave) {
        return (catalogo || {})[clave] || etiqueta(clave);
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
