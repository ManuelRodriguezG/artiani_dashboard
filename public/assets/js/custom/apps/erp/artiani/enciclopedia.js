/**
 * IA: Codex GPT-5 | Fecha: 2026-09-29
 * Proposito: operar el listado de Enciclopedia de especies Artiani.
 * Impacto: UI Conocimiento/Atencion; separa busqueda/listado de la ficha detallada.
 * Contrato: consume endpoints read-only `/artiani/*_erp` y navega a `/artiani/especie/{slug}`.
 */
(function () {
    const ENDPOINT_CATALOGOS = "/artiani/catalogos_erp";
    const ENDPOINT_LISTAR = "/artiani/especies_listar_erp";
    let catalogos = { grupos: {}, dificultades: {} };

    document.addEventListener("DOMContentLoaded", function () {
        enlazarEventos();
        cargarCatalogos().then(cargarLista);
    });

    function enlazarEventos() {
        on("artiani_btn_buscar", "click", cargarLista);
        on("artiani_btn_recargar", "click", cargarLista);
        on("artiani_btn_limpiar", "click", function () {
            setValue("artiani_busqueda", "");
            setValue("artiani_grupo", "");
            setValue("artiani_dificultad", "");
            cargarLista();
        });
        const busqueda = document.getElementById("artiani_busqueda");
        if (busqueda) {
            busqueda.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    cargarLista();
                }
            });
        }
    }

    function cargarCatalogos() {
        return consultarJson(ENDPOINT_CATALOGOS).then(function (respuesta) {
            catalogos = respuesta.depurar || catalogos;
            llenarSelect("artiani_grupo", catalogos.grupos || {}, "Todos los grupos");
            llenarSelect("artiani_dificultad", catalogos.dificultades || {}, "Todas");
        }).catch(function (error) {
            setText("artiani_estado", error.message);
        });
    }

    function cargarLista() {
        const params = new URLSearchParams();
        params.set("q", valor("artiani_busqueda"));
        params.set("grupo", valor("artiani_grupo"));
        params.set("dificultad", valor("artiani_dificultad"));
        setText("artiani_estado", "Consultando fichas Artiani...");

        consultarJson(ENDPOINT_LISTAR + "?" + params.toString()).then(function (respuesta) {
            const datos = respuesta.depurar || {};
            renderLista(datos.especies || []);
            setText("artiani_total", String(datos.total || 0) + " fichas");
            setText("artiani_estado", "Listado listo. Abre una ficha para revisar todos sus detalles.");
        }).catch(function (error) {
            setText("artiani_estado", error.message);
        });
    }

    function renderLista(especies) {
        const lista = document.getElementById("artiani_lista");
        if (!lista) {
            return;
        }
        if (!especies.length) {
            lista.innerHTML = '<tr><td colspan="5" class="text-muted">No hay especies con esos filtros.</td></tr>';
            return;
        }
        lista.innerHTML = especies.map(function (item) {
            return '<tr>' +
                '<td><div class="fw-bold text-gray-900">' + escapeHtml(item.nombre) + '</div><div class="text-muted fs-8">' + escapeHtml(item.resumen) + '</div></td>' +
                '<td><span class="badge badge-light-primary">' + escapeHtml(nombreCatalogo(catalogos.grupos, item.grupo)) + '</span></td>' +
                '<td><span class="badge badge-light">' + escapeHtml(nombreCatalogo(catalogos.dificultades, item.dificultad)) + '</span></td>' +
                '<td><div class="d-flex flex-wrap gap-2">' + (item.productos_puente || []).map(function (producto) {
                    return '<span class="badge badge-light-info">' + escapeHtml(producto) + '</span>';
                }).join("") + '</div></td>' +
                '<td class="text-end"><a class="btn btn-sm btn-light-primary" href="/artiani/especie/' + encodeURIComponent(item.slug) + '"><i class="bi bi-box-arrow-up-right"></i> Abrir ficha</a></td>' +
            '</tr>';
        }).join("");
    }

    function consultarJson(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible consultar Artiani");
            }
            return respuesta.json();
        });
    }

    function llenarSelect(id, valores, placeholder) {
        const select = document.getElementById(id);
        if (!select) {
            return;
        }
        let html = '<option value="">' + escapeHtml(placeholder) + '</option>';
        html += Object.keys(valores).map(function (clave) {
            return '<option value="' + escapeHtml(clave) + '">' + escapeHtml(valores[clave]) + '</option>';
        }).join("");
        select.innerHTML = html;
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
