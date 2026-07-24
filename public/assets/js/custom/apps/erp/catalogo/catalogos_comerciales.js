(function () {
    "use strict";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-23
     * Proposito: operar el MVP read-only de catalogos comerciales desde Catalogo ERP.
     * Impacto: UI Catalogo ERP/Comercial; permite filtrar candidatos y previsualizar tarjetas sin guardar BD.
     * Contrato: consume GET `/catalogoerp/catalogos_comerciales_candidatos`; la seleccion se guarda solo en localStorage del navegador.
     */
    const STORAGE_KEY = "erp_catalogos_comerciales_mvp_seleccion";

    const estado = {
        items: [],
        seleccion: new Map()
    };

    const $ = (id) => document.getElementById(id);

    function escapeHtml(valor) {
        return String(valor ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function imagenHtml(url, clase) {
        const ruta = normalizarRutaImagen(url);
        if (!ruta) {
            return `<div class="${clase === "cc-thumb" ? "cc-empty-img" : ""}"><i class="bi bi-image"></i></div>`;
        }
        return `<img class="${clase}" src="${escapeHtml(ruta)}" alt="">`;
    }

    function normalizarRutaImagen(url) {
        const valor = String(url || "").trim();
        if (!valor) return "";
        if (/^https?:\/\//i.test(valor) || valor.startsWith("/")) return valor;
        return `/${valor.replace(/^\/+/, "")}`;
    }

    function dinero(valor, moneda) {
        const numero = Number(valor);
        if (!Number.isFinite(numero) || numero <= 0) return "Sin precio";
        return new Intl.NumberFormat("es-MX", { style: "currency", currency: moneda || "MXN" }).format(numero);
    }

    function badgeAlerta(alerta) {
        const mapa = {
            sin_imagen: "badge-light-danger",
            sin_precio: "badge-light-warning",
            sin_categoria: "badge-light-danger",
            sin_publicacion: "badge-light-info",
            venta_fraccionaria: "badge-light-primary",
            presentacion_preparada: "badge-light-success",
            paquete_configurable: "badge-light-success"
        };
        return `<span class="badge ${mapa[alerta] || "badge-light"}">${escapeHtml(alerta.replace(/_/g, " "))}</span>`;
    }

    function setEstado(texto, tipo) {
        const el = $("cc_estado");
        if (!el) return;
        el.className = `badge badge-light-${tipo || "primary"}`;
        el.textContent = texto;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: conservar la seleccion temporal del MVP aunque el operador recargue la pantalla.
     * Impacto: UI Catalogos comerciales; reduce perdida accidental de trabajo sin crear registros ERP.
     * Contrato: usa localStorage del navegador; no escribe BD, no comparte la seleccion con otros usuarios.
     */
    function guardarSeleccionLocal() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(estado.seleccion.values())));
        } catch (e) {
            // LocalStorage puede estar bloqueado; el MVP debe seguir funcionando en memoria.
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: restaurar seleccion temporal guardada localmente para continuar un armado en curso.
     * Impacto: UI Catalogos comerciales; evita confundir esto con persistencia formal de catalogos.
     * Contrato: ignora datos corruptos y solo reconstruye una seleccion local por `id_sku`.
     */
    function cargarSeleccionLocal() {
        try {
            const datos = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
            if (!Array.isArray(datos)) return;
            datos.forEach((item) => {
                if (item && item.id_sku) estado.seleccion.set(String(item.id_sku), item);
            });
        } catch (e) {
            estado.seleccion.clear();
        }
    }

    function filtros() {
        const params = new URLSearchParams();
        params.set("q", $("cc_q")?.value || "");
        params.set("limite", $("cc_limite")?.value || "48");
        params.set("solo_alertas", $("cc_alertas")?.value || "0");
        params.set("solo_con_imagen", $("cc_imagen")?.value || "0");
        params.set("modo_precio", $("cc_modo_precio")?.value || "indistinto");
        return params;
    }

    async function cargar() {
        setEstado("Cargando", "warning");
        const res = await fetch(`/catalogoerp/catalogos_comerciales_candidatos?${filtros().toString()}`, {
            credentials: "same-origin"
        });
        const json = await res.json();
        if (json.error) {
            throw new Error(json.mensaje || "No se pudieron cargar candidatos");
        }
        const depurar = json.depurar || {};
        estado.items = Array.isArray(depurar.items) ? depurar.items : [];
        renderResumen(depurar.resumen || {});
        renderTabla();
        renderSeleccion();
        setEstado("Listo", "success");
    }

    function renderResumen(resumen) {
        const set = (id, valor) => { if ($(id)) $(id).textContent = Number(valor || 0).toLocaleString("es-MX"); };
        set("cc_res_total", resumen.total);
        set("cc_res_alertas", resumen.con_alertas);
        set("cc_res_imagen", resumen.sin_imagen);
        set("cc_res_precio", resumen.sin_precio);
        set("cc_res_paquetes", resumen.paquetes);
        set("cc_res_sel", estado.seleccion.size);
    }

    function renderTabla() {
        const body = $("cc_body");
        if (!body) return;
        if (!estado.items.length) {
            body.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-8">Sin candidatos</td></tr>`;
            return;
        }
        body.innerHTML = estado.items.map((item) => {
            const seleccionado = estado.seleccion.has(String(item.id_sku));
            const alertas = Array.isArray(item.alertas) && item.alertas.length
                ? item.alertas.slice(0, 4).map(badgeAlerta).join("")
                : `<span class="badge badge-light-success">sin alertas</span>`;
            return `<tr>
                <td>${imagenHtml(item.imagen_portada, "cc-thumb")}</td>
                <td>
                    <div class="fw-bold text-gray-900">${escapeHtml(item.nombre)}</div>
                    <div class="text-muted">${escapeHtml(item.sku)} · ${escapeHtml(item.tipo_item)} · ${escapeHtml(item.marca || "Sin marca")}</div>
                    <div class="text-muted">${escapeHtml(item.presentacion_comercial || "")}</div>
                </td>
                <td>${escapeHtml(item.categoria || "Sin categoria")}</td>
                <td class="fw-semibold">${escapeHtml(dinero(item.precio, item.moneda))}</td>
                <td><div class="cc-alerts">${alertas}</div></td>
                <td class="text-end">
                    <button class="btn btn-sm ${seleccionado ? "btn-light-danger" : "btn-light-primary"}" type="button" data-cc-toggle="${escapeHtml(item.id_sku)}">
                        <i class="bi ${seleccionado ? "bi-dash-circle" : "bi-plus-circle"}"></i>
                    </button>
                </td>
            </tr>`;
        }).join("");
    }

    function renderSeleccion() {
        const contenedor = $("cc_seleccion");
        const preview = $("cc_preview");
        if (!contenedor || !preview) return;
        const items = Array.from(estado.seleccion.values());
        if ($("cc_res_sel")) $("cc_res_sel").textContent = items.length.toLocaleString("es-MX");

        if (!items.length) {
            contenedor.innerHTML = `<div class="text-muted py-4">Sin items seleccionados</div>`;
            preview.innerHTML = `<div class="text-muted py-5">Sin vista previa</div>`;
            return;
        }

        contenedor.innerHTML = items.map((item, index) => `<div class="d-flex align-items-center gap-3 border rounded p-2">
            ${imagenHtml(item.imagen_portada, "cc-thumb")}
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-truncate">${escapeHtml(item.nombre)}</div>
                <div class="text-muted fs-8 text-truncate">${escapeHtml(item.sku)} · ${escapeHtml(item.categoria || "Sin categoria")}</div>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-icon btn-sm btn-light" type="button" data-cc-up="${escapeHtml(item.id_sku)}" ${index === 0 ? "disabled" : ""}><i class="bi bi-arrow-up"></i></button>
                <button class="btn btn-icon btn-sm btn-light" type="button" data-cc-down="${escapeHtml(item.id_sku)}" ${index === items.length - 1 ? "disabled" : ""}><i class="bi bi-arrow-down"></i></button>
            </div>
            <button class="btn btn-icon btn-sm btn-light-danger" type="button" data-cc-remove="${escapeHtml(item.id_sku)}"><i class="bi bi-x"></i></button>
        </div>`).join("");

        const mostrarPrecio = Boolean($("cc_mostrar_precio")?.checked);
        const mostrarSku = Boolean($("cc_mostrar_sku")?.checked);
        const mostrarDisponibilidad = Boolean($("cc_mostrar_disponibilidad")?.checked);

        preview.innerHTML = items.map((item) => `<article class="cc-card">
            <div class="cc-card__media">${imagenHtml(item.imagen_portada, "")}</div>
            <div class="cc-card__body">
                <div class="cc-card__title">${escapeHtml(item.nombre)}</div>
                <div class="cc-card__meta">${escapeHtml(item.marca || "")}</div>
                <div class="cc-card__meta">${escapeHtml(item.presentacion_comercial || item.sku)}</div>
                ${mostrarSku ? `<div class="cc-card__meta">${escapeHtml(item.sku)}</div>` : ""}
                ${mostrarDisponibilidad ? `<div class="cc-card__meta">${escapeHtml(item.disponibilidad_simple || "consultar")}</div>` : ""}
                ${mostrarPrecio ? `<div class="cc-card__price">${escapeHtml(dinero(item.precio, item.moneda))}</div>` : ""}
            </div>
        </article>`).join("");
    }

    function toggleSku(idSku) {
        const id = String(idSku);
        if (estado.seleccion.has(id)) {
            estado.seleccion.delete(id);
        } else {
            const item = estado.items.find((actual) => String(actual.id_sku) === id);
            if (item) estado.seleccion.set(id, item);
        }
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: reordenar tarjetas seleccionadas antes de imprimir o validar la galeria.
     * Impacto: UI Catalogos comerciales; permite definir narrativa visual sin persistencia formal.
     * Contrato: solo reordena localStorage/seleccion temporal; no escribe BD.
     */
    function moverSeleccion(idSku, direccion) {
        const id = String(idSku);
        const items = Array.from(estado.seleccion.values());
        const index = items.findIndex((item) => String(item.id_sku) === id);
        const destino = index + direccion;
        if (index < 0 || destino < 0 || destino >= items.length) return;
        const temporal = items[index];
        items[index] = items[destino];
        items[destino] = temporal;
        estado.seleccion = new Map(items.map((item) => [String(item.id_sku), item]));
        guardarSeleccionLocal();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: aplicar seleccion masiva sobre los candidatos actualmente cargados.
     * Impacto: UI Catalogos comerciales; acelera armado de galerias sin persistir datos ni tocar Catalogo maestro.
     * Contrato: modifica seleccion local/localStorage; no llama endpoints de escritura.
     */
    function seleccionarVisibles() {
        estado.items.forEach((item) => {
            estado.seleccion.set(String(item.id_sku), item);
        });
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: quitar en bloque de la seleccion temporal los candidatos visibles.
     * Impacto: UI Catalogos comerciales; permite corregir selecciones masivas sin limpiar todo el catalogo armado.
     * Contrato: modifica seleccion local/localStorage; no borra productos ni registros.
     */
    function quitarVisibles() {
        estado.items.forEach((item) => {
            estado.seleccion.delete(String(item.id_sku));
        });
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    function enlazarEventos() {
        $("cc_buscar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_recargar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_seleccionar_visibles")?.addEventListener("click", seleccionarVisibles);
        $("cc_quitar_visibles")?.addEventListener("click", quitarVisibles);
        $("cc_limpiar")?.addEventListener("click", () => {
            estado.seleccion.clear();
            guardarSeleccionLocal();
            renderTabla();
            renderSeleccion();
        });
        ["cc_mostrar_precio", "cc_mostrar_sku", "cc_mostrar_disponibilidad"].forEach((id) => {
            $(id)?.addEventListener("change", renderSeleccion);
        });
        $("cc_q")?.addEventListener("keydown", (event) => {
            if (event.key === "Enter") cargar().catch(mostrarError);
        });
        document.addEventListener("click", (event) => {
            const toggle = event.target.closest("[data-cc-toggle]");
            if (toggle) toggleSku(toggle.getAttribute("data-cc-toggle"));
            const remove = event.target.closest("[data-cc-remove]");
            if (remove) toggleSku(remove.getAttribute("data-cc-remove"));
            const up = event.target.closest("[data-cc-up]");
            if (up) moverSeleccion(up.getAttribute("data-cc-up"), -1);
            const down = event.target.closest("[data-cc-down]");
            if (down) moverSeleccion(down.getAttribute("data-cc-down"), 1);
        });
    }

    function mostrarError(error) {
        setEstado("Error", "danger");
        if (window.Swal) {
            Swal.fire("Catalogos comerciales", error.message || "No se pudo completar la consulta", "error");
        } else {
            alert(error.message || "No se pudo completar la consulta");
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        cargarSeleccionLocal();
        enlazarEventos();
        renderSeleccion();
        cargar().catch(mostrarError);
    });
})();
