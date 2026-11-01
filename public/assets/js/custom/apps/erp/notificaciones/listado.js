/**
 * ERP Notificaciones - Bandeja general
 * IA: Codex GPT-6 | Fecha: 2026-10-31
 * Proposito: listar alertas y separarlas por modulo de origen, area responsable y tipo.
 * Impacto: bandeja general de notificaciones ERP; los filtros solo consultan datos existentes.
 */
(function () {
    const ENDPOINT_RESUMEN = "/sistema/notificaciones_resumen_erp";
    const ENDPOINT_LISTAR = "/sistema/notificaciones_listar_erp";
    const ENDPOINT_LEER = "/sistema/notificacion_marcar_leida_erp";
    let filtroModulo = "";
    let filtroArea = "";

    document.addEventListener("DOMContentLoaded", function () {
        enlazarEventos();
        cargarBandeja();
    });

    /**
     * IA: Codex GPT-6 | Fecha: 2026-10-31
     * Proposito: enlaza filtros de origen/area, estado y acciones de lectura.
     * Impacto: UX de la bandeja de notificaciones.
     */
    function enlazarEventos() {
        const refrescar = document.getElementById("notificaciones_refrescar");
        const estatus = document.getElementById("notificaciones_estatus");

        if (refrescar) {
            refrescar.addEventListener("click", cargarBandeja);
        }
        if (estatus) {
            estatus.addEventListener("change", cargarBandeja);
        }
        document.addEventListener("click", function (event) {
            const modulo = event.target.closest("[data-notificaciones-modulo]");
            const area = event.target.closest("[data-notificaciones-area]");
            if (modulo) {
                filtroModulo = modulo.getAttribute("data-notificaciones-modulo") || "";
                cargarBandeja();
                return;
            }
            if (area) {
                filtroArea = area.getAttribute("data-notificaciones-area") || "";
                cargarBandeja();
            }
        });

        document.addEventListener("click", function (event) {
            const boton = event.target.closest(".notificaciones-marcar-leida");
            if (!boton) {
                return;
            }
            event.preventDefault();
            marcarLeida(boton);
        });
    }

    function cargarBandeja() {
        Promise.all([
            consultarJson(ENDPOINT_RESUMEN),
            consultarJson(ENDPOINT_LISTAR + "?" + filtrosQuery())
        ]).then(function (respuestas) {
            const resumen = respuestas[0];
            const listado = respuestas[1];
            if (resumen && resumen.error === false) {
                renderResumen(resumen.depurar || {});
            }
            if (listado && listado.error === false) {
                renderListado(Array.isArray(listado.depurar) ? listado.depurar : []);
            } else {
                renderError(listado ? listado.mensaje : "No fue posible consultar notificaciones");
            }
        }).catch(function (error) {
            renderError(error.message);
        });
    }

    function filtrosQuery() {
        const params = new URLSearchParams();
        params.append("limite", "50");

        const estatus = valor("notificaciones_estatus");
        if (filtroArea) { params.append("area_responsable", filtroArea); }
        if (filtroModulo) { params.append("modulo_origen", filtroModulo); }
        if (estatus) {
            params.append("estatus", estatus);
        }
        return params.toString();
    }

    function consultarJson(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible consultar notificaciones");
            }
            return respuesta.json();
        });
    }

    function renderResumen(resumen) {
        setText("notificaciones_total", numero(resumen.total_pendientes));
        setText("notificaciones_criticas", numero(resumen.criticas));
        setText("notificaciones_altas", numero(resumen.altas));
        setText("notificaciones_areas_total", Array.isArray(resumen.por_area) ? resumen.por_area.length : 0);
        renderModulos(Array.isArray(resumen.por_modulo) ? resumen.por_modulo : []);
        renderAreas(Array.isArray(resumen.por_area) ? resumen.por_area : []);
    }

    function renderModulos(modulos) {
        const contenedor = document.getElementById("notificaciones_modulos");
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = '<button type="button" class="btn btn-sm ' + (!filtroModulo ? "btn-primary" : "btn-light-primary") + '" data-notificaciones-modulo="">Todos</button>' + modulos.map(function (modulo) {
            const key = modulo.modulo_origen || "";
            return '<button type="button" class="btn btn-sm ' + (filtroModulo === key ? "btn-primary" : "btn-light-primary") + '" data-notificaciones-modulo="' + escapeHtml(key) + '">' + escapeHtml(etiquetaOrigen(key)) + ' <span class="badge badge-light ms-1">' + numero(modulo.total) + '</span></button>';
        }).join("");
    }

    /** IA: Codex GPT-6 | Fecha: 2026-10-31 | Crea filtros por area de atencion usando los conteos visibles del resumen. */
    function renderAreas(areas) {
        const contenedor = document.getElementById("notificaciones_areas");
        if (!contenedor) { return; }
        contenedor.innerHTML = '<button type="button" class="btn btn-sm ' + (!filtroArea ? "btn-primary" : "btn-light-primary") + '" data-notificaciones-area="">Todas</button>' + areas.map(function (area) {
            const key = area.area_responsable || "";
            return '<button type="button" class="btn btn-sm ' + (filtroArea === key ? "btn-primary" : "btn-light-primary") + '" data-notificaciones-area="' + escapeHtml(key) + '">' + escapeHtml(etiquetaOrigen(key)) + ' <span class="badge badge-light ms-1">' + numero(area.total) + '</span></button>';
        }).join("");
    }

    function renderListado(items) {
        const body = document.getElementById("notificaciones_body");
        if (!body) {
            return;
        }
        if (!items.length) {
            body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-10">Sin notificaciones para los filtros seleccionados</td></tr>';
            return;
        }

        body.innerHTML = items.map(renderFila).join("");
    }

    function renderFila(item) {
        const prioridad = item.prioridad || "normal";
        const leida = numero(item.leida) === 1;
        const url = urlNotificacion(item);
        const accionPrincipal = url === "#"
            ? ""
            : '<a class="btn btn-sm btn-light-primary me-2" href="' + url + '">Abrir</a>';
        const accionLectura = leida
            ? '<button type="button" class="btn btn-sm btn-light" disabled>Leida</button>'
            : '<button type="button" class="btn btn-sm btn-light notificaciones-marcar-leida" data-id="' + numero(item.id_notificacion) + '">Marcar leida</button>';

        return '' +
            '<tr>' +
                '<td><span class="' + clasePrioridad(prioridad) + '">' + escapeHtml(prioridad) + '</span></td>' +
                '<td>' +
                    '<div class="' + (leida ? "fw-semibold" : "fw-bold") + ' text-gray-800">' + escapeHtml(item.titulo || "Notificacion") + '</div>' +
                    '<div class="text-muted fs-7">' + escapeHtml(item.descripcion || "") + '</div>' +
                '</td>' +
                '<td>' + escapeHtml(etiquetaOrigen(item.modulo_origen || "general")) + '</td>' +
                '<td>' + escapeHtml(etiquetaOrigen(item.area_responsable || "general")) + '</td>' +
                '<td><span class="badge badge-light-info">' + escapeHtml(etiquetaOrigen(item.tipo || "general")) + '</span></td>' +
                '<td><span class="badge badge-light">' + escapeHtml(item.estatus || "") + '</span></td>' +
                '<td>' + escapeHtml(formatearFecha(item.fecha_registro)) + '</td>' +
                '<td class="text-end">' + accionPrincipal + accionLectura + '</td>' +
            '</tr>';
    }

    function marcarLeida(boton) {
        const id = numero(boton.getAttribute("data-id"));
        if (id <= 0) {
            return;
        }
        boton.disabled = true;
        const datos = new URLSearchParams();
        datos.append("id_notificacion", String(id));

        fetch(ENDPOINT_LEER, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible marcar como leida");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            if (respuesta.error === false) {
                cargarBandeja();
            } else {
                boton.disabled = false;
            }
        }).catch(function () {
            boton.disabled = false;
        });
    }

    function renderError(mensaje) {
        const body = document.getElementById("notificaciones_body");
        if (body) {
            body.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-10">' + escapeHtml(mensaje || "Error al consultar notificaciones") + '</td></tr>';
        }
    }

    function clasePrioridad(prioridad) {
        prioridad = String(prioridad || "").toLowerCase();
        if (prioridad === "critica") {
            return "badge badge-light-danger";
        }
        if (prioridad === "alta") {
            return "badge badge-light-warning";
        }
        if (prioridad === "info") {
            return "badge badge-light-info";
        }
        return "badge badge-light-primary";
    }

    function normalizarUrl(url) {
        if (!url || typeof url !== "string") {
            return "#";
        }
        if (url.charAt(0) === "/" || url.indexOf("http://") === 0 || url.indexOf("https://") === 0) {
            return escapeHtml(url);
        }
        return "#";
    }

    /** IA: Codex GPT-6 | Fecha: 2026-10-31 | Dirige también avisos históricos ya guardados a su bandeja resolutiva. */
    function urlNotificacion(item) {
        if (item && item.tipo === "venta_historica_regularizacion") {
            var payload = item.payload_json || {};
            if (typeof payload === "string") { try { payload = JSON.parse(payload); } catch (e) { payload = {}; } }
            if (payload && payload.accion === "clasificar_sku") {
                return "/ventas/acciones_historicas?notificacion=" + encodeURIComponent(item.id_notificacion || "");
            }
        }
        return normalizarUrl(item ? item.url_accion : "");
    }

    function formatearFecha(fecha) {
        if (!fecha) {
            return "";
        }
        const parsed = new Date(String(fecha).replace(" ", "T"));
        if (Number.isNaN(parsed.getTime())) {
            return "";
        }
        return parsed.toLocaleDateString("es-MX", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function valor(id) {
        const elemento = document.getElementById(id);
        return elemento ? String(elemento.value || "").trim() : "";
    }

    /** IA: Codex GPT-6 | Fecha: 2026-10-31 | Traduce claves técnicas simples para mostrar origen y tipo de manera legible. */
    function etiquetaOrigen(valor) {
        const etiquetas = {
            ventas_pos: "Ventas y POS",
            ecommerce_publico: "Ecommerce",
            catalogo: "Catálogo",
            compras: "Compras",
            almacen: "Almacén",
            inventario: "Inventario",
            finanzas: "Finanzas",
            proveedores: "Proveedores",
            rentabilidad: "Rentabilidad",
            venta_historica_regularizacion: "Regularización histórica",
            venta_rapida_sku_sin_match: "SKU de venta rápida pendiente",
            general: "General"
        };
        const key = String(valor || "general");
        if (etiquetas[key]) { return etiquetas[key]; }
        return key.replace(/[_-]+/g, " ").replace(/\b\w/g, function (letra) { return letra.toUpperCase(); });
    }

    function setText(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = String(texto);
        }
    }

    function numero(valor) {
        const n = Number(valor);
        return Number.isFinite(n) ? n : 0;
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
