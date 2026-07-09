/**
 * ERP Notificaciones
 * Documentacion IA: Codex GPT-5
 * Version: 1.0
 * Descripcion: conecta el navbar global con las alertas operativas visibles por permisos.
 */
(function () {
    const ENDPOINT_RESUMEN = "/sistema/notificaciones_resumen_erp";
    const ENDPOINT_LISTAR = "/sistema/notificaciones_listar_erp?limite=8";
    const ENDPOINT_LEER = "/sistema/notificacion_marcar_leida_erp";

    const state = {
        cargando: false,
        notificaciones: [],
        resumen: {
            total_pendientes: 0,
            criticas: 0,
            altas: 0,
            por_area: []
        }
    };

    document.addEventListener("DOMContentLoaded", function () {
        if (!document.getElementById("erp_notificaciones_toggle")) {
            return;
        }

        prepararContenedores();
        cargarNotificaciones();
        window.setInterval(cargarNotificaciones, 60000);
    });

    /**
     * ERP Notificaciones
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Prepara los tabs del dropdown para sustituir contenido demo del template.
     */
    function prepararContenedores() {
        const alertas = document.getElementById("kt_topbar_notifications_1");
        const pendientes = document.getElementById("kt_topbar_notifications_2");
        const leidas = document.getElementById("kt_topbar_notifications_3");

        if (alertas) {
            alertas.innerHTML = '<div id="erp_notificaciones_lista" class="scroll-y mh-325px my-5 px-8"><div class="text-center text-muted py-10">Cargando notificaciones...</div></div><div class="py-3 text-center border-top"><a href="/sistema/notificaciones" class="btn btn-sm btn-light-primary">Ver bandeja</a></div>';
        }
        if (pendientes) {
            pendientes.innerHTML = '<div id="erp_notificaciones_areas" class="scroll-y mh-325px my-5 px-8"><div class="text-center text-muted py-10">Sin pendientes por area</div></div>';
        }
        if (leidas) {
            leidas.innerHTML = '<div class="px-9 py-10 text-center text-muted">El historial de notificaciones leidas se habilitara en una siguiente etapa.</div>';
        }
    }

    /**
     * ERP Notificaciones
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Consulta resumen y listado sin bloquear la pagina actual.
     */
    function cargarNotificaciones() {
        if (state.cargando) {
            return;
        }
        state.cargando = true;

        Promise.all([
            consultarJson(ENDPOINT_RESUMEN),
            consultarJson(ENDPOINT_LISTAR)
        ]).then(function (respuestas) {
            const resumen = respuestas[0];
            const listado = respuestas[1];

            if (resumen && resumen.error === false && resumen.depurar) {
                state.resumen = resumen.depurar;
            }
            if (listado && listado.error === false && Array.isArray(listado.depurar)) {
                state.notificaciones = listado.depurar;
            }

            renderResumen();
            renderListado();
            renderAreas();
        }).catch(function () {
            renderErrorSilencioso();
        }).finally(function () {
            state.cargando = false;
        });
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

    function renderResumen() {
        const total = numero(state.resumen.total_pendientes);
        const criticas = numero(state.resumen.criticas);
        const altas = numero(state.resumen.altas);
        const badge = document.getElementById("erp_notificaciones_badge");
        const resumen = document.getElementById("erp_notificaciones_resumen");
        const toggle = document.getElementById("erp_notificaciones_toggle");

        if (badge) {
            badge.textContent = total > 99 ? "99+" : String(total);
            badge.classList.toggle("d-none", total <= 0);
            badge.classList.toggle("badge-danger", criticas > 0);
            badge.classList.toggle("badge-warning", criticas <= 0 && altas > 0);
            badge.classList.toggle("badge-primary", criticas <= 0 && altas <= 0);
        }

        if (resumen) {
            resumen.textContent = total === 1 ? "1 pendiente" : total + " pendientes";
        }

        if (toggle) {
            toggle.classList.toggle("btn-active-color-danger", criticas > 0);
        }
    }

    function renderListado() {
        const contenedor = document.getElementById("erp_notificaciones_lista");
        if (!contenedor) {
            return;
        }

        if (!state.notificaciones.length) {
            contenedor.innerHTML = '<div class="text-center text-muted py-10">Sin alertas pendientes</div>';
            return;
        }

        contenedor.innerHTML = state.notificaciones.map(renderItemNotificacion).join("");
    }

    function renderItemNotificacion(item) {
        const prioridad = (item.prioridad || "normal").toLowerCase();
        const badge = clasePrioridad(prioridad);
        const url = normalizarUrl(item.url_accion);
        const leida = numero(item.leida) === 1;
        const descripcion = item.descripcion ? '<div class="text-gray-500 fs-7">' + escapeHtml(item.descripcion) + '</div>' : "";
        const area = item.area_responsable ? escapeHtml(item.area_responsable) : "general";
        const fecha = formatearFecha(item.fecha_registro);

        const accionLeida = leida
            ? '<button type="button" class="btn btn-sm btn-light" disabled>Leida</button>'
            : '<button type="button" class="btn btn-sm btn-light-primary erp-notificacion-leer" data-id="' + numero(item.id_notificacion) + '">Marcar</button>';

        return '' +
            '<div class="d-flex flex-stack py-4 border-bottom border-gray-200">' +
                '<div class="d-flex align-items-start me-3">' +
                    '<div class="symbol symbol-35px me-4 mt-1"><span class="symbol-label ' + badge.fondo + '">' +
                        '<span class="fw-bold ' + badge.texto + '">' + badge.inicial + '</span>' +
                    '</span></div>' +
                    '<div class="mb-0 me-2">' +
                        '<a href="' + url + '" class="fs-6 text-gray-800 text-hover-primary ' + (leida ? "fw-semibold" : "fw-bold") + '">' + escapeHtml(item.titulo || "Notificacion") + '</a>' +
                        descripcion +
                        '<div class="text-muted fs-8 mt-1">' + area + (fecha ? " - " + fecha : "") + '</div>' +
                    '</div>' +
                '</div>' +
                accionLeida +
            '</div>';
    }

    function renderAreas() {
        const contenedor = document.getElementById("erp_notificaciones_areas");
        const areas = Array.isArray(state.resumen.por_area) ? state.resumen.por_area : [];
        if (!contenedor) {
            return;
        }

        if (!areas.length) {
            contenedor.innerHTML = '<div class="text-center text-muted py-10">Sin pendientes por area</div>';
            return;
        }

        contenedor.innerHTML = areas.map(function (area) {
            return '' +
                '<div class="d-flex flex-stack py-4 border-bottom border-gray-200">' +
                    '<div class="fw-semibold text-gray-800">' + escapeHtml(area.area_responsable || "general") + '</div>' +
                    '<span class="badge badge-light-primary">' + numero(area.total) + '</span>' +
                '</div>';
        }).join("");
    }

    function renderErrorSilencioso() {
        const badge = document.getElementById("erp_notificaciones_badge");
        if (badge) {
            badge.classList.add("d-none");
        }
    }

    document.addEventListener("click", function (event) {
        const boton = event.target.closest(".erp-notificacion-leer");
        if (!boton) {
            return;
        }
        event.preventDefault();
        marcarLeida(boton);
    });

    /**
     * ERP Notificaciones
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Marca una notificacion como leida y refresca el contador global.
     */
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
                throw new Error("No fue posible marcar la notificacion");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            if (respuesta.error === false) {
                cargarNotificaciones();
            } else {
                boton.disabled = false;
            }
        }).catch(function () {
            boton.disabled = false;
        });
    }

    function clasePrioridad(prioridad) {
        if (prioridad === "critica") {
            return { fondo: "bg-light-danger", texto: "text-danger", inicial: "!" };
        }
        if (prioridad === "alta") {
            return { fondo: "bg-light-warning", texto: "text-warning", inicial: "A" };
        }
        if (prioridad === "info") {
            return { fondo: "bg-light-info", texto: "text-info", inicial: "I" };
        }
        return { fondo: "bg-light-primary", texto: "text-primary", inicial: "N" };
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
            hour: "2-digit",
            minute: "2-digit"
        });
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
