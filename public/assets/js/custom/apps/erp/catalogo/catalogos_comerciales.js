(function () {
    "use strict";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-23
     * Proposito: operar el MVP read-only de catalogos comerciales desde Catalogo ERP.
     * Impacto: UI Catalogo ERP/Comercial; permite filtrar candidatos y previsualizar tarjetas sin guardar BD.
     * Contrato: consume GET `/catalogoerp/catalogos_comerciales_candidatos`; la seleccion se guarda solo en localStorage del navegador.
     */
    const STORAGE_KEY = "erp_catalogos_comerciales_mvp_seleccion";
    const STORAGE_META_KEY = "erp_catalogos_comerciales_mvp_material";
    const STORAGE_DRAFTS_KEY = "erp_catalogos_comerciales_mvp_borradores";
    const CANDIDATOS_POR_PAGINA = 12;
    const SELECCION_POR_PAGINA = 8;

    const estado = {
        items: [],
        seleccion: new Map(),
        paginaCandidatos: 1,
        paginaSeleccion: 1
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: normalizar la plantilla visual elegida para la previsualizacion comercial.
     * Impacto: UI Catalogos comerciales; permite alternar formatos de redes y catalogo compacto sin persistir configuracion.
     * Contrato: solo acepta plantillas conocidas y usa `square` como formato seguro por defecto.
     */
    function plantillaActual() {
        const valor = $("cc_plantilla")?.value || "square";
        return ["square", "story", "compact"].includes(valor) ? valor : "square";
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: conservar datos visuales del material comercial durante el MVP.
     * Impacto: UI Catalogos comerciales; permite imprimir/capturar con encabezado sin persistencia formal.
     * Contrato: usa localStorage local del navegador; no crea catalogos ni publica enlaces.
     */
    function guardarMaterialLocal() {
        try {
            const datos = {
                titulo: $("cc_material_titulo")?.value || "",
                subtitulo: $("cc_material_subtitulo")?.value || "",
                cta: $("cc_material_cta")?.value || "",
                portadaActiva: Boolean($("cc_portada_activa")?.checked),
                portadaEtiqueta: $("cc_portada_etiqueta")?.value || "",
                portadaDescripcion: $("cc_portada_descripcion")?.value || "",
                portadaNota: $("cc_portada_nota")?.value || ""
            };
            localStorage.setItem(STORAGE_META_KEY, JSON.stringify(datos));
        } catch (e) {
            // El encabezado puede operar sin persistencia local si el navegador la bloquea.
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: restaurar encabezado temporal de material comercial.
     * Impacto: UI Catalogos comerciales; evita perder titulo/subtitulo/CTA al recargar durante pruebas.
     * Contrato: solo hidrata inputs locales y usa defaults seguros cuando no hay datos.
     */
    function cargarMaterialLocal() {
        let datos = {};
        try {
            datos = JSON.parse(localStorage.getItem(STORAGE_META_KEY) || "{}") || {};
        } catch (e) {
            datos = {};
        }
        if ($("cc_material_titulo")) $("cc_material_titulo").value = datos.titulo || "Catalogo de productos";
        if ($("cc_material_subtitulo")) $("cc_material_subtitulo").value = datos.subtitulo || "";
        if ($("cc_material_cta")) $("cc_material_cta").value = datos.cta || "Pregunta por disponibilidad";
        if ($("cc_portada_activa")) $("cc_portada_activa").checked = datos.portadaActiva !== false;
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = datos.portadaEtiqueta || "Catalogo recomendado";
        if ($("cc_portada_descripcion")) $("cc_portada_descripcion").value = datos.portadaDescripcion || "";
        if ($("cc_portada_nota")) $("cc_portada_nota").value = datos.portadaNota || "";
    }

    function materialActual() {
        return {
            titulo: $("cc_material_titulo")?.value || "",
            subtitulo: $("cc_material_subtitulo")?.value || "",
            cta: $("cc_material_cta")?.value || "",
            portadaActiva: Boolean($("cc_portada_activa")?.checked),
            portadaEtiqueta: $("cc_portada_etiqueta")?.value || "",
            portadaDescripcion: $("cc_portada_descripcion")?.value || "",
            portadaNota: $("cc_portada_nota")?.value || ""
        };
    }

    function aplicarMaterial(datos) {
        const material = datos || {};
        if ($("cc_material_titulo")) $("cc_material_titulo").value = material.titulo || "Catalogo de productos";
        if ($("cc_material_subtitulo")) $("cc_material_subtitulo").value = material.subtitulo || "";
        if ($("cc_material_cta")) $("cc_material_cta").value = material.cta || "Pregunta por disponibilidad";
        if ($("cc_portada_activa")) $("cc_portada_activa").checked = material.portadaActiva !== false;
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = material.portadaEtiqueta || "Catalogo recomendado";
        if ($("cc_portada_descripcion")) $("cc_portada_descripcion").value = material.portadaDescripcion || "";
        if ($("cc_portada_nota")) $("cc_portada_nota").value = material.portadaNota || "";
        guardarMaterialLocal();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: leer borradores locales del MVP de catalogos comerciales.
     * Impacto: UI Catalogos comerciales; permite varios armados temporales sin crear registros ERP.
     * Contrato: devuelve objeto por nombre; ignora datos corruptos y no escribe BD.
     */
    function leerBorradoresLocales() {
        try {
            const datos = JSON.parse(localStorage.getItem(STORAGE_DRAFTS_KEY) || "{}");
            return datos && typeof datos === "object" && !Array.isArray(datos) ? datos : {};
        } catch (e) {
            return {};
        }
    }

    function guardarBorradoresLocales(datos) {
        try {
            localStorage.setItem(STORAGE_DRAFTS_KEY, JSON.stringify(datos || {}));
        } catch (e) {
            throw new Error("No se pudo guardar el borrador local en este navegador");
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: refrescar el selector de borradores guardados localmente.
     * Impacto: UI Catalogos comerciales; mantiene visible que son borradores locales del navegador.
     * Contrato: no consulta servidor ni valida permisos; solo lee localStorage.
     */
    function renderBorradoresLocales() {
        const select = $("cc_borradores_guardados");
        if (!select) return;
        const valorActual = select.value;
        const nombres = Object.keys(leerBorradoresLocales()).sort((a, b) => a.localeCompare(b, "es"));
        select.innerHTML = nombres.length
            ? `<option value="">Selecciona borrador</option>${nombres.map((nombre) => `<option value="${escapeHtml(nombre)}">${escapeHtml(nombre)}</option>`).join("")}`
            : `<option value="">Sin borradores</option>`;
        if (nombres.includes(valorActual)) select.value = valorActual;
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

    function paginasTotales(total, porPagina) {
        return Math.max(1, Math.ceil(Number(total || 0) / porPagina));
    }

    function paginaNormalizada(pagina, total, porPagina) {
        return Math.min(Math.max(1, Number(pagina || 1)), paginasTotales(total, porPagina));
    }

    function paginaItems(items, pagina, porPagina) {
        const total = Array.isArray(items) ? items.length : 0;
        const paginaActual = paginaNormalizada(pagina, total, porPagina);
        const inicio = (paginaActual - 1) * porPagina;
        return {
            pagina: paginaActual,
            totalPaginas: paginasTotales(total, porPagina),
            items: (items || []).slice(inicio, inicio + porPagina),
            total,
            inicio: total ? inicio + 1 : 0,
            fin: Math.min(inicio + porPagina, total)
        };
    }

    function renderPaginador(info, prefijo) {
        const etiqueta = $(`${prefijo}_paginacion_info`);
        const anterior = $(`${prefijo}_prev`);
        const siguiente = $(`${prefijo}_next`);
        if (etiqueta) {
            etiqueta.textContent = info.total
                ? `Pagina ${info.pagina} de ${info.totalPaginas} - ${info.inicio}-${info.fin} de ${info.total}`
                : "Pagina 1 de 1";
        }
        if (anterior) anterior.disabled = info.pagina <= 1;
        if (siguiente) siguiente.disabled = info.pagina >= info.totalPaginas;
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
        estado.paginaCandidatos = 1;
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
            renderPaginador({ total: 0, pagina: 1, totalPaginas: 1, inicio: 0, fin: 0 }, "cc_cand");
            return;
        }
        const pagina = paginaItems(estado.items, estado.paginaCandidatos, CANDIDATOS_POR_PAGINA);
        estado.paginaCandidatos = pagina.pagina;
        renderPaginador(pagina, "cc_cand");
        body.innerHTML = pagina.items.map((item) => {
            const seleccionado = estado.seleccion.has(String(item.id_sku));
            const alertas = Array.isArray(item.alertas) && item.alertas.length
                ? item.alertas.slice(0, 4).map(badgeAlerta).join("")
                : `<span class="badge badge-light-success">sin alertas</span>`;
            return `<tr>
                <td>${imagenHtml(item.imagen_portada, "cc-thumb")}</td>
                <td>
                    <div class="fw-bold text-gray-900">${escapeHtml(item.nombre)}</div>
                    <div class="text-muted">${escapeHtml(item.sku)} - ${escapeHtml(item.tipo_item)} - ${escapeHtml(item.marca || "Sin marca")}</div>
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
        renderEncabezadoMaterial();
        const items = Array.from(estado.seleccion.values());
        if ($("cc_res_sel")) $("cc_res_sel").textContent = items.length.toLocaleString("es-MX");
        const plantilla = plantillaActual();
        preview.className = `cc-preview-grid cc-preview-grid--${plantilla}`;

        if (!items.length) {
            contenedor.innerHTML = `<div class="text-muted py-4">Sin items seleccionados</div>`;
            preview.innerHTML = `<div class="text-muted py-5">Sin vista previa</div>`;
            renderPaginador({ total: 0, pagina: 1, totalPaginas: 1, inicio: 0, fin: 0 }, "cc_sel");
            return;
        }

        const paginaSeleccion = paginaItems(items, estado.paginaSeleccion, SELECCION_POR_PAGINA);
        estado.paginaSeleccion = paginaSeleccion.pagina;
        renderPaginador(paginaSeleccion, "cc_sel");

        contenedor.innerHTML = paginaSeleccion.items.map((item) => {
            const index = items.findIndex((actual) => String(actual.id_sku) === String(item.id_sku));
            return `<div class="d-flex align-items-center gap-3 border rounded p-2">
            ${imagenHtml(item.imagen_portada, "cc-thumb")}
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-truncate">${escapeHtml(item.nombre)}</div>
                <div class="text-muted fs-8 text-truncate">${escapeHtml(item.sku)} - ${escapeHtml(item.categoria || "Sin categoria")}</div>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-icon btn-sm btn-light" type="button" data-cc-up="${escapeHtml(item.id_sku)}" ${index === 0 ? "disabled" : ""}><i class="bi bi-arrow-up"></i></button>
                <button class="btn btn-icon btn-sm btn-light" type="button" data-cc-down="${escapeHtml(item.id_sku)}" ${index === items.length - 1 ? "disabled" : ""}><i class="bi bi-arrow-down"></i></button>
            </div>
            <button class="btn btn-icon btn-sm btn-light-danger" type="button" data-cc-remove="${escapeHtml(item.id_sku)}"><i class="bi bi-x"></i></button>
        </div>`;
        }).join("");

        const mostrarPrecio = Boolean($("cc_mostrar_precio")?.checked);
        const mostrarMarca = Boolean($("cc_mostrar_marca")?.checked);
        const mostrarCategoria = Boolean($("cc_mostrar_categoria")?.checked);
        const mostrarPresentacion = Boolean($("cc_mostrar_presentacion")?.checked);
        const mostrarSku = Boolean($("cc_mostrar_sku")?.checked);
        const mostrarDisponibilidad = Boolean($("cc_mostrar_disponibilidad")?.checked);

        preview.innerHTML = items.map((item) => `<article class="cc-card">
            <div class="cc-card__media">${imagenHtml(item.imagen_portada, "")}</div>
            <div class="cc-card__body">
                <div class="cc-card__title">${escapeHtml(item.nombre)}</div>
                ${mostrarMarca && item.marca ? `<div class="cc-card__meta">${escapeHtml(item.marca)}</div>` : ""}
                ${mostrarCategoria ? `<div class="cc-card__meta">${escapeHtml(item.categoria || "Sin categoria")}</div>` : ""}
                ${mostrarPresentacion ? `<div class="cc-card__meta">${escapeHtml(item.presentacion_comercial || item.sku)}</div>` : ""}
                ${mostrarSku ? `<div class="cc-card__meta">${escapeHtml(item.sku)}</div>` : ""}
                ${mostrarDisponibilidad ? `<div class="cc-card__meta">${escapeHtml(item.disponibilidad_simple || "consultar")}</div>` : ""}
                ${mostrarPrecio ? `<div class="cc-card__price">${escapeHtml(dinero(item.precio, item.moneda))}</div>` : ""}
            </div>
        </article>`).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: pintar encabezado comercial temporal en la vista previa.
     * Impacto: UI Catalogos comerciales; mejora capturas/impresion inicial sin generar archivos.
     * Contrato: escapa texto capturado y oculta el bloque si no hay titulo/subtitulo/CTA.
     */
    function renderEncabezadoMaterial() {
        const contenedor = $("cc_preview_header");
        if (!contenedor) return;
        const titulo = ($("cc_material_titulo")?.value || "").trim();
        const subtitulo = ($("cc_material_subtitulo")?.value || "").trim();
        const cta = ($("cc_material_cta")?.value || "").trim();
        const portadaActiva = Boolean($("cc_portada_activa")?.checked);
        const portadaEtiqueta = ($("cc_portada_etiqueta")?.value || "").trim();
        const portadaDescripcion = ($("cc_portada_descripcion")?.value || "").trim();
        const portadaNota = ($("cc_portada_nota")?.value || "").trim();
        guardarMaterialLocal();
        if (!titulo && !subtitulo && !cta && !portadaActiva) {
            contenedor.innerHTML = "";
            return;
        }
        const encabezado = `<section class="cc-preview-header">
            ${titulo ? `<h3 class="cc-preview-header__title">${escapeHtml(titulo)}</h3>` : ""}
            ${subtitulo ? `<div class="cc-preview-header__subtitle">${escapeHtml(subtitulo)}</div>` : ""}
            ${cta ? `<div class="cc-preview-header__cta">${escapeHtml(cta)}</div>` : ""}
        </section>`;
        const portada = portadaActiva ? `<section class="cc-cover-card">
            ${portadaEtiqueta ? `<div class="cc-cover-card__label">${escapeHtml(portadaEtiqueta)}</div>` : ""}
            ${titulo ? `<h3 class="cc-cover-card__title">${escapeHtml(titulo)}</h3>` : ""}
            ${portadaDescripcion || subtitulo ? `<div class="cc-cover-card__desc">${escapeHtml(portadaDescripcion || subtitulo)}</div>` : ""}
            ${portadaNota || cta ? `<div class="cc-cover-card__cta">${escapeHtml(portadaNota || cta)}</div>` : ""}
        </section>` : "";
        contenedor.innerHTML = `${portada}${titulo || subtitulo || cta ? encabezado : ""}`;
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
        const pagina = paginaItems(estado.items, estado.paginaCandidatos, CANDIDATOS_POR_PAGINA);
        pagina.items.forEach((item) => {
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
        const pagina = paginaItems(estado.items, estado.paginaCandidatos, CANDIDATOS_POR_PAGINA);
        pagina.items.forEach((item) => {
            estado.seleccion.delete(String(item.id_sku));
        });
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: alternar una vista limpia para capturar o presentar el catalogo comercial.
     * Impacto: UI Catalogos comerciales; oculta navegacion y controles sin modificar seleccion ni escribir BD.
     * Contrato: solo agrega/quita clase CSS en `body`; no genera archivos ni cambia permisos.
     */
    function alternarModoCaptura() {
        const activo = document.body.classList.toggle("cc-capture-mode");
        const boton = $("cc_modo_captura");
        if (boton) {
            boton.innerHTML = activo
                ? `<i class="bi bi-x-lg"></i> Salir captura`
                : `<i class="bi bi-aspect-ratio"></i> Modo captura`;
            boton.className = activo ? "btn btn-dark" : "btn btn-light-primary";
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: construir texto comercial basico desde la seleccion temporal para compartir por chat o redes.
     * Impacto: UI Catalogos comerciales; acelera trabajo operativo sin generar archivos ni guardar catalogos.
     * Contrato: solo lee seleccion local y opciones visibles; no consulta costos, stock exacto ni escribe BD.
     */
    function textoListadoSeleccionado() {
        const items = Array.from(estado.seleccion.values());
        const titulo = ($("cc_material_titulo")?.value || "").trim();
        const subtitulo = ($("cc_material_subtitulo")?.value || "").trim();
        const cta = ($("cc_material_cta")?.value || "").trim();
        const mostrarPrecio = Boolean($("cc_mostrar_precio")?.checked);
        const mostrarMarca = Boolean($("cc_mostrar_marca")?.checked);
        const mostrarCategoria = Boolean($("cc_mostrar_categoria")?.checked);
        const mostrarPresentacion = Boolean($("cc_mostrar_presentacion")?.checked);
        const mostrarSku = Boolean($("cc_mostrar_sku")?.checked);
        const mostrarDisponibilidad = Boolean($("cc_mostrar_disponibilidad")?.checked);
        const lineas = [];

        if (titulo) lineas.push(titulo);
        if (subtitulo) lineas.push(subtitulo);
        if (titulo || subtitulo) lineas.push("");

        items.forEach((item, index) => {
            const partes = [`${index + 1}. ${item.nombre || "Producto"}`];
            if (mostrarMarca && item.marca) partes.push(item.marca);
            if (mostrarCategoria) partes.push(item.categoria || "Sin categoria");
            if (mostrarPresentacion && item.presentacion_comercial) partes.push(item.presentacion_comercial);
            if (mostrarPrecio) partes.push(dinero(item.precio, item.moneda));
            if (mostrarSku) partes.push(`SKU ${item.sku || ""}`.trim());
            if (mostrarDisponibilidad) partes.push(item.disponibilidad_simple || "consultar disponibilidad");
            lineas.push(partes.filter(Boolean).join(" - "));
        });

        if (cta) {
            lineas.push("");
            lineas.push(cta);
        }

        return lineas.join("\n");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: copiar al portapapeles un listado textual del catalogo temporal.
     * Impacto: UI Catalogos comerciales; facilita enviar seleccion por WhatsApp/Facebook sin integraciones externas.
     * Contrato: requiere items seleccionados; si Clipboard API falla usa seleccion temporal de textarea.
     */
    async function copiarListado() {
        const texto = textoListadoSeleccionado();
        if (!texto.trim() || !estado.seleccion.size) {
            throw new Error("Selecciona al menos un producto para copiar el listado");
        }

        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(texto);
            return;
        }

        const temporal = document.createElement("textarea");
        temporal.value = texto;
        temporal.setAttribute("readonly", "readonly");
        temporal.style.position = "fixed";
        temporal.style.left = "-9999px";
        document.body.appendChild(temporal);
        temporal.select();
        document.execCommand("copy");
        document.body.removeChild(temporal);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: reiniciar por completo el borrador local del material comercial.
     * Impacto: UI Catalogos comerciales; permite empezar otro armado sin borrar datos maestros ni registros.
     * Contrato: limpia localStorage del MVP y restablece campos temporales; no escribe ni borra BD.
     */
    function reiniciarBorradorLocal() {
        estado.seleccion.clear();
        try {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(STORAGE_META_KEY);
        } catch (e) {
            // Si localStorage no esta disponible, basta con limpiar el estado en memoria.
        }
        if ($("cc_material_titulo")) $("cc_material_titulo").value = "Catalogo de productos";
        if ($("cc_material_subtitulo")) $("cc_material_subtitulo").value = "";
        if ($("cc_material_cta")) $("cc_material_cta").value = "Pregunta por disponibilidad";
        if ($("cc_portada_activa")) $("cc_portada_activa").checked = true;
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = "Catalogo recomendado";
        if ($("cc_portada_descripcion")) $("cc_portada_descripcion").value = "";
        if ($("cc_portada_nota")) $("cc_portada_nota").value = "";
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: guardar el armado actual como borrador local con nombre.
     * Impacto: UI Catalogos comerciales; permite alternar propuestas comerciales sin DDL ni registros.
     * Contrato: exige nombre e items seleccionados; persiste solo en localStorage del navegador.
     */
    function guardarBorradorNombrado() {
        const nombre = ($("cc_borrador_nombre")?.value || "").trim();
        if (!nombre) throw new Error("Captura un nombre para el borrador");
        if (!estado.seleccion.size) throw new Error("Selecciona al menos un producto para guardar el borrador");
        const borradores = leerBorradoresLocales();
        borradores[nombre] = {
            nombre,
            fecha: new Date().toISOString(),
            material: materialActual(),
            seleccion: Array.from(estado.seleccion.values())
        };
        guardarBorradoresLocales(borradores);
        renderBorradoresLocales();
        if ($("cc_borradores_guardados")) $("cc_borradores_guardados").value = nombre;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: restaurar un borrador local nombrado al armado activo.
     * Impacto: UI Catalogos comerciales; recupera seleccion y encabezado temporal sin tocar catalogo maestro.
     * Contrato: solo carga datos guardados en localStorage; ignora items sin `id_sku`.
     */
    function cargarBorradorNombrado() {
        const nombre = $("cc_borradores_guardados")?.value || "";
        if (!nombre) throw new Error("Selecciona un borrador para cargar");
        const borrador = leerBorradoresLocales()[nombre];
        if (!borrador) throw new Error("No se encontro el borrador seleccionado");
        estado.seleccion.clear();
        (Array.isArray(borrador.seleccion) ? borrador.seleccion : []).forEach((item) => {
            if (item && item.id_sku) estado.seleccion.set(String(item.id_sku), item);
        });
        aplicarMaterial(borrador.material || {});
        if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = nombre;
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: eliminar un borrador local nombrado.
     * Impacto: UI Catalogos comerciales; limpia propuestas temporales sin borrar productos ni catalogos reales.
     * Contrato: solo modifica localStorage del navegador; no escribe BD.
     */
    function eliminarBorradorNombrado() {
        const nombre = $("cc_borradores_guardados")?.value || "";
        if (!nombre) throw new Error("Selecciona un borrador para eliminar");
        const borradores = leerBorradoresLocales();
        delete borradores[nombre];
        guardarBorradoresLocales(borradores);
        if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = "";
        renderBorradoresLocales();
    }

    function nombreArchivoSeguro(nombre) {
        return String(nombre || "catalogo-comercial")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-zA-Z0-9_-]+/g, "-")
            .replace(/^-+|-+$/g, "")
            .toLowerCase() || "catalogo-comercial";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: exportar un borrador local como JSON portable.
     * Impacto: UI Catalogos comerciales; permite mover propuestas entre navegadores sin persistencia ERP.
     * Contrato: exporta solo material y seleccion guardados en localStorage; no consulta servidor ni escribe BD.
     */
    function exportarBorradorNombrado() {
        const nombre = $("cc_borradores_guardados")?.value || "";
        if (!nombre) throw new Error("Selecciona un borrador para exportar");
        const borrador = leerBorradoresLocales()[nombre];
        if (!borrador) throw new Error("No se encontro el borrador seleccionado");
        const payload = {
            formato: "erp_catalogo_comercial_borrador_local",
            version: 1,
            exportado_en: new Date().toISOString(),
            borrador
        };
        const blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement("a");
        enlace.href = url;
        enlace.download = `${nombreArchivoSeguro(nombre)}.json`;
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        URL.revokeObjectURL(url);
    }

    function validarBorradorImportado(datos) {
        const borrador = datos && datos.formato === "erp_catalogo_comercial_borrador_local"
            ? datos.borrador
            : datos;
        if (!borrador || typeof borrador !== "object") {
            throw new Error("El archivo no contiene un borrador valido");
        }
        const nombre = String(borrador.nombre || "").trim();
        const seleccion = Array.isArray(borrador.seleccion) ? borrador.seleccion : [];
        if (!nombre) throw new Error("El borrador importado no tiene nombre");
        if (!seleccion.length) throw new Error("El borrador importado no tiene productos seleccionados");
        return {
            nombre,
            fecha: borrador.fecha || new Date().toISOString(),
            material: borrador.material || {},
            seleccion
        };
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: importar un borrador local desde JSON.
     * Impacto: UI Catalogos comerciales; facilita compartir armados temporales antes de crear persistencia formal.
     * Contrato: valida formato basico, guarda en localStorage y no escribe BD.
     */
    function importarBorradorDesdeArchivo(archivo) {
        if (!archivo) return Promise.resolve(false);
        return archivo.text().then((contenido) => {
            let datos;
            try {
                datos = JSON.parse(contenido);
            } catch (e) {
                throw new Error("El archivo no es un JSON valido");
            }
            const borrador = validarBorradorImportado(datos);
            const borradores = leerBorradoresLocales();
            borradores[borrador.nombre] = borrador;
            guardarBorradoresLocales(borradores);
            renderBorradoresLocales();
            if ($("cc_borradores_guardados")) $("cc_borradores_guardados").value = borrador.nombre;
            if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = borrador.nombre;
            return true;
        });
    }

    function enlazarEventos() {
        $("cc_buscar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_recargar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_cand_prev")?.addEventListener("click", () => {
            estado.paginaCandidatos -= 1;
            renderTabla();
        });
        $("cc_cand_next")?.addEventListener("click", () => {
            estado.paginaCandidatos += 1;
            renderTabla();
        });
        $("cc_sel_prev")?.addEventListener("click", () => {
            estado.paginaSeleccion -= 1;
            renderSeleccion();
        });
        $("cc_sel_next")?.addEventListener("click", () => {
            estado.paginaSeleccion += 1;
            renderSeleccion();
        });
        $("cc_seleccionar_visibles")?.addEventListener("click", seleccionarVisibles);
        $("cc_quitar_visibles")?.addEventListener("click", quitarVisibles);
        $("cc_modo_captura")?.addEventListener("click", alternarModoCaptura);
        $("cc_copiar_listado")?.addEventListener("click", () => {
            copiarListado()
                .then(() => setEstado("Listado copiado", "success"))
                .catch(mostrarError);
        });
        $("cc_guardar_borrador")?.addEventListener("click", () => {
            try {
                guardarBorradorNombrado();
                setEstado("Borrador guardado", "success");
            } catch (error) {
                mostrarError(error);
            }
        });
        $("cc_cargar_borrador")?.addEventListener("click", () => {
            try {
                cargarBorradorNombrado();
                setEstado("Borrador cargado", "success");
            } catch (error) {
                mostrarError(error);
            }
        });
        $("cc_exportar_borrador")?.addEventListener("click", () => {
            try {
                exportarBorradorNombrado();
                setEstado("Borrador exportado", "success");
            } catch (error) {
                mostrarError(error);
            }
        });
        $("cc_importar_borrador")?.addEventListener("click", () => {
            $("cc_importar_borrador_archivo")?.click();
        });
        $("cc_importar_borrador_archivo")?.addEventListener("change", (event) => {
            const archivo = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            importarBorradorDesdeArchivo(archivo)
                .then((importado) => {
                    if (importado) setEstado("Borrador importado", "success");
                    event.target.value = "";
                })
                .catch((error) => {
                    event.target.value = "";
                    mostrarError(error);
                });
        });
        $("cc_eliminar_borrador")?.addEventListener("click", () => {
            const ejecutar = () => {
                try {
                    eliminarBorradorNombrado();
                    setEstado("Borrador eliminado", "success");
                } catch (error) {
                    mostrarError(error);
                }
            };
            if (window.Swal) {
                Swal.fire({
                    title: "Eliminar borrador local",
                    text: "Solo se eliminara de este navegador.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar"
                }).then((resultado) => {
                    if (resultado.isConfirmed) ejecutar();
                });
                return;
            }
            if (confirm("Solo se eliminara de este navegador. Continuar?")) ejecutar();
        });
        $("cc_reiniciar_borrador")?.addEventListener("click", () => {
            const ejecutar = () => {
                reiniciarBorradorLocal();
                setEstado("Borrador reiniciado", "success");
            };
            if (window.Swal) {
                Swal.fire({
                    title: "Reiniciar borrador",
                    text: "Se limpiara la seleccion y los datos temporales de este navegador.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Reiniciar",
                    cancelButtonText: "Cancelar"
                }).then((resultado) => {
                    if (resultado.isConfirmed) ejecutar();
                });
                return;
            }
            if (confirm("Se limpiara la seleccion y los datos temporales de este navegador. Continuar?")) ejecutar();
        });
        $("cc_limpiar")?.addEventListener("click", () => {
            estado.seleccion.clear();
            guardarSeleccionLocal();
            renderTabla();
            renderSeleccion();
        });
        ["cc_mostrar_precio", "cc_mostrar_marca", "cc_mostrar_categoria", "cc_mostrar_presentacion", "cc_mostrar_sku", "cc_mostrar_disponibilidad", "cc_plantilla"].forEach((id) => {
            $(id)?.addEventListener("change", renderSeleccion);
        });
        ["cc_material_titulo", "cc_material_subtitulo", "cc_material_cta", "cc_portada_etiqueta", "cc_portada_descripcion", "cc_portada_nota"].forEach((id) => {
            $(id)?.addEventListener("input", renderSeleccion);
        });
        $("cc_portada_activa")?.addEventListener("change", renderSeleccion);
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
        cargarMaterialLocal();
        renderBorradoresLocales();
        enlazarEventos();
        renderSeleccion();
        cargar().catch(mostrarError);
    });
})();
