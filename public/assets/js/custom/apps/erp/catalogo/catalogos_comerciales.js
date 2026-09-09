(function () {
    "use strict";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-23
     * Proposito: operar catalogos comerciales persistentes desde Catalogo ERP.
     * Impacto: UI Catalogo ERP/Comercial; permite filtrar candidatos, previsualizar tarjetas y guardar borradores en BD.
     * Contrato: consume endpoints `/catalogoerp/catalogos_comerciales_*`; exporta PNG en navegador sin crear archivos en servidor.
     */
    const STORAGE_KEY = "erp_catalogos_comerciales_mvp_seleccion";
    const STORAGE_META_KEY = "erp_catalogos_comerciales_mvp_material";
    const STORAGE_OPTIONS_KEY = "erp_catalogos_comerciales_mvp_opciones";
    const STORAGE_DRAFTS_KEY = "erp_catalogos_comerciales_mvp_borradores";
    const CANDIDATOS_POR_PAGINA = 12;
    const SELECCION_POR_PAGINA = 8;

    const estado = {
        items: [],
        seleccion: new Map(),
        paginaCandidatos: 1,
        paginaSeleccion: 1,
        catalogos: [],
        categorias: [],
        catalogoActualId: 0,
        modoInicial: "listado",
        catalogoInicialId: 0,
        nuevoInicial: false
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

    function apiGet(url) {
        return fetch(url, { credentials: "same-origin" }).then((response) => response.json());
    }

    function apiPost(url, data) {
        return fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: new URLSearchParams(data || {}).toString(),
            credentials: "same-origin"
        }).then((response) => response.json());
    }

    function apiPostFormData(url, data) {
        return fetch(url, {
            method: "POST",
            body: data,
            credentials: "same-origin"
        }).then((response) => response.json());
    }

    function dinero(valor, moneda) {
        const numero = Number(valor);
        if (!Number.isFinite(numero) || numero <= 0) return "Sin precio";
        return new Intl.NumberFormat("es-MX", { style: "currency", currency: moneda || "MXN" }).format(numero);
    }

    function valorColor(id, fallback) {
        const valor = String($(id)?.value || fallback || "").trim();
        return /^#[0-9a-f]{6}$/i.test(valor) ? valor : fallback;
    }

    function valorEntero(id, permitidos, fallback) {
        const valor = Number($(id)?.value || fallback);
        return permitidos.includes(valor) ? valor : fallback;
    }

    function fuenteVisualActual() {
        const valor = $("cc_fuente_visual")?.value || "arial";
        const mapa = {
            arial: "Arial, sans-serif",
            verdana: "Verdana, Geneva, sans-serif",
            georgia: "Georgia, serif",
            trebuchet: "'Trebuchet MS', Arial, sans-serif",
            impacto: "Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif"
        };
        return mapa[valor] ? { clave: valor, familia: mapa[valor] } : { clave: "arial", familia: mapa.arial };
    }

    function estiloVisualActual() {
        const fuente = fuenteVisualActual();
        return {
            fuente: fuente.clave,
            familiaFuente: fuente.familia,
            colorTitulo: valorColor("cc_color_titulo", "#181c32"),
            colorProducto: valorColor("cc_color_producto", "#181c32"),
            colorMeta: valorColor("cc_color_meta", "#5e6278"),
            colorPrecio: valorColor("cc_color_precio", "#0f7a5f"),
            tamTitulo: valorEntero("cc_tam_titulo", [21, 23, 26, 30], 23),
            tamProducto: valorEntero("cc_tam_producto", [10, 11, 13, 15], 11),
            tamMeta: valorEntero("cc_tam_meta", [8, 9, 10, 12], 9),
            tamPrecio: valorEntero("cc_tam_precio", [12, 13, 15, 18], 13)
        };
    }

    function aplicarEstiloVisual() {
        const estilo = estiloVisualActual();
        const area = document.querySelector(".cc-print-area");
        if (!area) return estilo;
        area.style.setProperty("--cc-font-family", estilo.familiaFuente);
        area.style.setProperty("--cc-title-color", estilo.colorTitulo);
        area.style.setProperty("--cc-product-color", estilo.colorProducto);
        area.style.setProperty("--cc-meta-color", estilo.colorMeta);
        area.style.setProperty("--cc-price-color", estilo.colorPrecio);
        area.style.setProperty("--cc-title-size", `${estilo.tamTitulo}px`);
        area.style.setProperty("--cc-product-size", `${estilo.tamProducto}px`);
        area.style.setProperty("--cc-meta-size", `${estilo.tamMeta}px`);
        area.style.setProperty("--cc-price-size", `${estilo.tamPrecio}px`);
        return estilo;
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: controlar densidad visual del catalogo exportable sin cambiar el esquema.
     * Impacto: UI Catalogos comerciales; permite generar PNG con productos mas grandes por fila.
     * Contrato: persiste la densidad como sufijo de plantilla (`square_3`, `story_2`) en la columna existente.
     */
    function columnasExportacionActual() {
        const columnas = Number($("cc_columnas_exportacion")?.value || 3);
        return [2, 3, 4, 5].includes(columnas) ? columnas : 3;
    }

    function filasExportacionActual() {
        const valor = $("cc_filas_exportacion")?.value || "auto";
        if (valor === "auto") return 0;
        const filas = Number(valor);
        return [2, 3, 4, 5, 6].includes(filas) ? filas : 0;
    }

    function plantillaBase(valor) {
        const base = String(valor || "square").split("_")[0];
        return ["square", "story", "compact"].includes(base) ? base : "square";
    }

    function columnasDesdePlantilla(valor) {
        const match = String(valor || "").match(/_(\d)(?:x(\d))?$/);
        const columnas = Number(match && match[1] ? match[1] : 3);
        return [2, 3, 4, 5].includes(columnas) ? columnas : 3;
    }

    function filasDesdePlantilla(valor) {
        const match = String(valor || "").match(/_(\d)x(\d)$/);
        const filas = Number(match && match[2] ? match[2] : 0);
        return [2, 3, 4, 5, 6].includes(filas) ? filas : 0;
    }

    function plantillaPersistenteActual() {
        const base = plantillaActual();
        if (base === "compact") return "compact";
        const columnas = columnasExportacionActual();
        const filas = filasExportacionActual();
        return filas > 0 ? `${base}_${columnas}x${filas}` : `${base}_${columnas}`;
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
                portadaTipo: $("cc_portada_tipo")?.value || "plantilla",
                portadaEtiqueta: $("cc_portada_etiqueta")?.value || "",
                portadaImagenUrl: $("cc_portada_imagen_url")?.value || "",
                logoUrl: $("cc_logo_url")?.value || "",
                contactoTexto: $("cc_contacto_texto")?.value || "",
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
        if ($("cc_portada_tipo")) $("cc_portada_tipo").value = datos.portadaTipo === "imagen_completa" ? "imagen_completa" : "plantilla";
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = datos.portadaEtiqueta || "Catalogo recomendado";
        if ($("cc_portada_imagen_url")) $("cc_portada_imagen_url").value = datos.portadaImagenUrl || "";
        if ($("cc_logo_url")) $("cc_logo_url").value = datos.logoUrl || "";
        if ($("cc_contacto_texto")) $("cc_contacto_texto").value = datos.contactoTexto || "";
        if ($("cc_portada_descripcion")) $("cc_portada_descripcion").value = datos.portadaDescripcion || "";
        if ($("cc_portada_nota")) $("cc_portada_nota").value = datos.portadaNota || "";
    }

    function guardarOpcionesLocal() {
        try {
            localStorage.setItem(STORAGE_OPTIONS_KEY, JSON.stringify(opcionesActuales()));
        } catch (e) {
            // La configuracion visual puede operar sin persistencia local.
        }
    }

    function cargarOpcionesLocal() {
        try {
            const datos = JSON.parse(localStorage.getItem(STORAGE_OPTIONS_KEY) || "{}") || {};
            aplicarOpciones(datos);
        } catch (e) {
            aplicarOpciones({});
        }
    }

    function materialActual() {
        return {
            titulo: $("cc_material_titulo")?.value || "",
            subtitulo: $("cc_material_subtitulo")?.value || "",
            cta: $("cc_material_cta")?.value || "",
            portadaActiva: Boolean($("cc_portada_activa")?.checked),
            portadaTipo: $("cc_portada_tipo")?.value || "plantilla",
            portadaEtiqueta: $("cc_portada_etiqueta")?.value || "",
            portadaImagenUrl: $("cc_portada_imagen_url")?.value || "",
            logoUrl: $("cc_logo_url")?.value || "",
            contactoTexto: $("cc_contacto_texto")?.value || "",
            portadaDescripcion: $("cc_portada_descripcion")?.value || "",
            portadaNota: $("cc_portada_nota")?.value || ""
        };
    }

    function opcionesActuales() {
        return {
            plantilla: plantillaPersistenteActual(),
            plantillaBase: plantillaActual(),
            columnasExportacion: columnasExportacionActual(),
            filasExportacion: filasExportacionActual(),
            estiloVisual: estiloVisualActual(),
            mostrarPrecio: Boolean($("cc_mostrar_precio")?.checked),
            mostrarMarca: Boolean($("cc_mostrar_marca")?.checked),
            mostrarCategoria: Boolean($("cc_mostrar_categoria")?.checked),
            mostrarPresentacion: Boolean($("cc_mostrar_presentacion")?.checked),
            mostrarSku: Boolean($("cc_mostrar_sku")?.checked),
            mostrarDisponibilidad: Boolean($("cc_mostrar_disponibilidad")?.checked),
            agruparVariantes: Boolean($("cc_agrupar_variantes")?.checked)
        };
    }

    function aplicarMaterial(datos) {
        const material = datos || {};
        if ($("cc_material_titulo")) $("cc_material_titulo").value = material.titulo || "Catalogo de productos";
        if ($("cc_material_subtitulo")) $("cc_material_subtitulo").value = material.subtitulo || "";
        if ($("cc_material_cta")) $("cc_material_cta").value = material.cta || "Pregunta por disponibilidad";
        if ($("cc_portada_activa")) $("cc_portada_activa").checked = material.portadaActiva !== false;
        if ($("cc_portada_tipo")) $("cc_portada_tipo").value = material.portadaTipo === "imagen_completa" ? "imagen_completa" : "plantilla";
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = material.portadaEtiqueta || "Catalogo recomendado";
        if ($("cc_portada_imagen_url")) $("cc_portada_imagen_url").value = material.portadaImagenUrl || "";
        if ($("cc_logo_url")) $("cc_logo_url").value = material.logoUrl || "";
        if ($("cc_contacto_texto")) $("cc_contacto_texto").value = material.contactoTexto || "";
        if ($("cc_portada_descripcion")) $("cc_portada_descripcion").value = material.portadaDescripcion || "";
        if ($("cc_portada_nota")) $("cc_portada_nota").value = material.portadaNota || "";
        guardarMaterialLocal();
    }

    function aplicarOpciones(datos) {
        const opciones = datos || {};
        const plantillaPersistida = opciones.plantilla || opciones.plantillaPersistente || "square";
        if ($("cc_plantilla")) $("cc_plantilla").value = plantillaBase(plantillaPersistida);
        if ($("cc_columnas_exportacion")) $("cc_columnas_exportacion").value = String(opciones.columnasExportacion || columnasDesdePlantilla(plantillaPersistida));
        const filasPersistidas = opciones.filasExportacion || filasDesdePlantilla(plantillaPersistida);
        if ($("cc_filas_exportacion")) $("cc_filas_exportacion").value = filasPersistidas ? String(filasPersistidas) : "auto";
        if ($("cc_mostrar_precio")) $("cc_mostrar_precio").checked = opciones.mostrarPrecio !== false;
        if ($("cc_mostrar_marca")) $("cc_mostrar_marca").checked = opciones.mostrarMarca !== false;
        if ($("cc_mostrar_categoria")) $("cc_mostrar_categoria").checked = Boolean(opciones.mostrarCategoria);
        if ($("cc_mostrar_presentacion")) $("cc_mostrar_presentacion").checked = opciones.mostrarPresentacion !== false;
        if ($("cc_mostrar_sku")) $("cc_mostrar_sku").checked = Boolean(opciones.mostrarSku);
        if ($("cc_mostrar_disponibilidad")) $("cc_mostrar_disponibilidad").checked = Boolean(opciones.mostrarDisponibilidad);
        if ($("cc_agrupar_variantes")) $("cc_agrupar_variantes").checked = Boolean(opciones.agruparVariantes);
        const estilo = opciones.estiloVisual || {};
        if ($("cc_fuente_visual")) $("cc_fuente_visual").value = estilo.fuente || "arial";
        if ($("cc_color_titulo")) $("cc_color_titulo").value = /^#[0-9a-f]{6}$/i.test(estilo.colorTitulo || "") ? estilo.colorTitulo : "#181c32";
        if ($("cc_color_producto")) $("cc_color_producto").value = /^#[0-9a-f]{6}$/i.test(estilo.colorProducto || "") ? estilo.colorProducto : "#181c32";
        if ($("cc_color_meta")) $("cc_color_meta").value = /^#[0-9a-f]{6}$/i.test(estilo.colorMeta || "") ? estilo.colorMeta : "#5e6278";
        if ($("cc_color_precio")) $("cc_color_precio").value = /^#[0-9a-f]{6}$/i.test(estilo.colorPrecio || "") ? estilo.colorPrecio : "#0f7a5f";
        if ($("cc_tam_titulo")) $("cc_tam_titulo").value = String(estilo.tamTitulo || 23);
        if ($("cc_tam_producto")) $("cc_tam_producto").value = String(estilo.tamProducto || 11);
        if ($("cc_tam_meta")) $("cc_tam_meta").value = String(estilo.tamMeta || 9);
        if ($("cc_tam_precio")) $("cc_tam_precio").value = String(estilo.tamPrecio || 13);
        aplicarEstiloVisual();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: leer la ruta operativa inicial de Catalogos comerciales.
     * Impacto: UI Comercial; permite abrir listado, nuevo, editar o ver sin duplicar vistas ni endpoints.
     * Contrato: consume inputs ocultos generados por PHP y no modifica BD.
     */
    function leerModoInicial() {
        const modo = $("cc_modo_inicial")?.value || "listado";
        estado.modoInicial = modo === "editor" || modo === "preview" ? modo : "guardados";
        estado.catalogoInicialId = Number($("cc_catalogo_inicial")?.value || 0);
        estado.nuevoInicial = String($("cc_nuevo_inicial")?.value || "0") === "1";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: ofrecer un selector de categorias ERP dentro del armado comercial.
     * Impacto: UI Catalogos comerciales; permite agregar candidatos por categoria sin duplicar reglas del catalogo maestro.
     * Contrato: lee `/catalogoerp/catalogos` y usa solo categorias activas que permiten productos.
     */
    async function cargarCategorias() {
        const select = $("cc_categoria");
        if (!select) return;
        const json = await apiGet("/catalogoerp/catalogos");
        if (json.error) throw new Error(json.mensaje || "No se pudieron cargar categorias");
        const categorias = json.depurar && Array.isArray(json.depurar.categorias) ? json.depurar.categorias : [];
        estado.categorias = categorias.filter((categoria) => Number(categoria.permite_productos ?? 1) === 1 && (!categoria.estatus || categoria.estatus === "activa"));
        select.innerHTML = `<option value="">Todas las categorias</option>${estado.categorias.map((categoria) => {
            const nombre = categoria.ruta || categoria.nombre || categoria.codigo || "Categoria";
            const total = Number(categoria.total_productos || 0);
            const sufijo = total > 0 ? ` (${total})` : "";
            return `<option value="${escapeHtml(categoria.id_categoria_erp)}">${escapeHtml(nombre + sufijo)}</option>`;
        }).join("")}`;
        activarBuscadorSelect(select);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: permitir escritura/busqueda en selectores largos cuando Select2 esta disponible.
     * Impacto: UX Catalogos comerciales; agiliza encontrar categorias sin perder compatibilidad con select nativo.
     * Contrato: mejora progresiva; no agrega dependencias nuevas ni cambia el valor enviado al filtro.
     */
    function activarBuscadorSelect(select) {
        if (!select || !window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
        const $select = jQuery(select);
        if ($select.data("select2")) $select.select2("destroy");
        $select.select2({
            width: "100%",
            dropdownParent: jQuery(document.body),
            placeholder: "Escribe una categoria",
            allowClear: true,
            language: {
                noResults: function () { return "Sin resultados"; },
                searching: function () { return "Buscando..."; }
            }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-26
     * Proposito: cargar catalogos comerciales persistidos en BD para continuar borradores entre sesiones.
     * Impacto: UI Catalogos comerciales; reemplaza el selector local como fuente principal.
     * Contrato: solo lista catalogos no archivados mediante `catalogos_comerciales_listar`.
     */
    async function cargarCatalogosGuardados() {
        const json = await apiGet("/catalogoerp/catalogos_comerciales_listar");
        if (json.error) throw new Error(json.mensaje || "No se pudieron listar catalogos guardados");
        estado.catalogos = json.depurar && Array.isArray(json.depurar.catalogos) ? json.depurar.catalogos : [];
        renderCatalogosGuardados();
        renderCatalogosGuardadosLista();
    }

    function renderCatalogosGuardados() {
        const select = $("cc_borradores_guardados");
        if (!select) return;
        const valorActual = String(select.value || estado.catalogoActualId || "");
        select.innerHTML = estado.catalogos.length
            ? `<option value="">Nuevo catalogo</option>${estado.catalogos.map((catalogo) => `<option value="${escapeHtml(catalogo.id_catalogo_comercial)}">${escapeHtml(catalogo.nombre)} (${escapeHtml(catalogo.total_items || 0)})</option>`).join("")}`
            : `<option value="">Sin catalogos guardados</option>`;
        if (estado.catalogos.some((catalogo) => String(catalogo.id_catalogo_comercial) === valorActual)) {
            select.value = valorActual;
        }
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
        params.set("id_categoria_erp", $("cc_categoria")?.value || "");
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
                    ${item.presentacion_comercial ? `<div class="text-muted">${escapeHtml(item.presentacion_comercial)}</div>` : ""}
                    ${item.variante_resumen ? `<div class="text-primary fs-8">${escapeHtml(item.variante_resumen)}</div>` : ""}
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

    function etiquetaVariante(item) {
        const resumen = String(item.variante_resumen || "").trim();
        if (resumen) return etiquetaValorVariante(resumen);
        return String(item.nombre || item.sku || "Variante").trim();
    }

    function etiquetaValorVariante(texto) {
        return String(texto || "")
            .split("|")
            .map((parte) => parte.trim().replace(/^[^:]{1,35}:\s*/, ""))
            .filter(Boolean)
            .join(" | ");
    }

    function precioTextoComercial(item) {
        if (item && item.precio_texto) return item.precio_texto;
        return dinero(item ? item.precio : null, item ? item.moneda : "MXN");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-27
     * Proposito: construir una representacion visual agrupada sin alterar la seleccion real de SKUs.
     * Impacto: Catalogos comerciales; permite mostrar variantes compactas en una sola tarjeta por producto.
     * Contrato: no fusiona ni borra SKUs; solo colapsa en memoria para preview, texto y PNG.
     */
    function itemsVisualesCatalogo() {
        const items = Array.from(estado.seleccion.values());
        if (!Boolean($("cc_agrupar_variantes")?.checked)) return items;
        const grupos = new Map();
        const salida = [];
        items.forEach((item) => {
            const idProducto = Number(item.id_producto_erp || 0);
            const esVariante = Boolean(item.maneja_variantes) || Number(item.variant_count || 0) > 0;
            if (idProducto <= 0 || !esVariante) {
                salida.push(item);
                return;
            }
            const clave = String(idProducto);
            if (!grupos.has(clave)) {
                grupos.set(clave, []);
                salida.push({ __grupoProducto: clave });
            }
            grupos.get(clave).push(item);
        });
        return salida.map((entrada) => {
            if (!entrada.__grupoProducto) return entrada;
            const variantes = grupos.get(entrada.__grupoProducto) || [];
            if (variantes.length <= 1) return variantes[0];
            const base = variantes[0] || {};
            const precios = variantes.map((item) => Number(item.precio)).filter((precio) => Number.isFinite(precio) && precio > 0);
            const min = precios.length ? Math.min(...precios) : null;
            const max = precios.length ? Math.max(...precios) : null;
            const moneda = variantes.find((item) => item.moneda)?.moneda || "MXN";
            const imagen = variantes.find((item) => item.imagen_portada)?.imagen_portada || base.imagen_portada || "";
            const precioTexto = min && max && min !== max ? `${dinero(min, moneda)} - ${dinero(max, moneda)}` : dinero(min, moneda);
            return {
                id_sku: `grupo-${entrada.__grupoProducto}`,
                id_producto_erp: Number(entrada.__grupoProducto),
                tipo_item: "grupo_variantes",
                tipo_visual: "grupo_variantes",
                codigo_producto: base.codigo_producto || "",
                sku: variantes.map((item) => item.sku).filter(Boolean).join(", "),
                nombre: base.producto || base.nombre || "Producto",
                producto: base.producto || base.nombre || "Producto",
                maneja_variantes: true,
                marca: base.marca || "",
                categoria: base.categoria || "",
                imagen_portada: imagen,
                presentacion_comercial: `${variantes.length} variantes`,
                precio: min,
                moneda,
                precio_texto: precioTexto,
                disponibilidad_simple: variantes.some((item) => item.disponibilidad_simple === "disponible") ? "disponible" : (base.disponibilidad_simple || "consultar"),
                variantes: variantes.map((item) => ({
                    id_sku: item.id_sku,
                    sku: item.sku,
                    nombre: item.nombre,
                    etiqueta: etiquetaVariante(item),
                    imagen_portada: item.imagen_portada,
                    precio: item.precio,
                    moneda: item.moneda,
                    disponibilidad_simple: item.disponibilidad_simple
                })),
                alertas: Array.from(new Set(variantes.flatMap((item) => Array.isArray(item.alertas) ? item.alertas : [])))
            };
        });
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
        aplicarEstiloVisual();
        if (plantilla === "compact") {
            preview.style.removeProperty("grid-template-columns");
        } else {
            preview.style.gridTemplateColumns = `repeat(${columnasExportacionActual()}, minmax(0, 1fr))`;
        }

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
        const visualItems = itemsVisualesCatalogo();

        preview.innerHTML = visualItems.map((item) => `<article class="cc-card">
            <div class="cc-card__media">${imagenHtml(item.imagen_portada, "")}</div>
            <div class="cc-card__body">
                <div class="cc-card__title">${escapeHtml(item.nombre)}</div>
                ${mostrarMarca && item.marca ? `<div class="cc-card__meta">${escapeHtml(item.marca)}</div>` : ""}
                ${mostrarCategoria ? `<div class="cc-card__meta">${escapeHtml(item.categoria || "Sin categoria")}</div>` : ""}
                ${mostrarPresentacion && item.presentacion_comercial ? `<div class="cc-card__meta">${escapeHtml(item.presentacion_comercial)}</div>` : ""}
                ${Array.isArray(item.variantes) && item.variantes.some((variante) => variante.imagen_portada) ? `<div class="cc-card__variant-images">${item.variantes.filter((variante) => variante.imagen_portada).slice(0, 6).map((variante) => `<img class="cc-card__variant-image" src="${escapeHtml(normalizarRutaImagen(variante.imagen_portada))}" alt="">`).join("")}</div>` : ""}
                ${Array.isArray(item.variantes) && item.variantes.length ? `<div class="cc-card__variants">${item.variantes.slice(0, 8).map((variante) => `<span class="cc-card__variant">${escapeHtml(variante.etiqueta || variante.sku || "Variante")}</span>`).join("")}</div>` : ""}
                ${mostrarSku ? `<div class="cc-card__meta">${escapeHtml(item.sku)}</div>` : ""}
                ${mostrarDisponibilidad ? `<div class="cc-card__meta">${escapeHtml(item.disponibilidad_simple || "consultar")}</div>` : ""}
                ${mostrarPrecio ? `<div class="cc-card__price">${escapeHtml(precioTextoComercial(item))}</div>` : ""}
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
        aplicarEstiloVisual();
        const titulo = ($("cc_material_titulo")?.value || "").trim();
        const subtitulo = ($("cc_material_subtitulo")?.value || "").trim();
        const cta = ($("cc_material_cta")?.value || "").trim();
        const portadaActiva = Boolean($("cc_portada_activa")?.checked);
        const portadaTipo = $("cc_portada_tipo")?.value || "plantilla";
        const portadaEtiqueta = ($("cc_portada_etiqueta")?.value || "").trim();
        const portadaImagenUrl = ($("cc_portada_imagen_url")?.value || "").trim();
        const logoUrl = ($("cc_logo_url")?.value || "").trim();
        const contactoTexto = ($("cc_contacto_texto")?.value || "").trim();
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
        const logo = logoUrl ? `<img class="cc-cover-card__logo" src="${escapeHtml(normalizarRutaImagen(logoUrl))}" alt="">` : "";
        const contacto = contactoTexto ? `<div class="cc-cover-card__cta">${escapeHtml(contactoTexto)}</div>` : "";
        const portada = portadaActiva ? (portadaTipo === "imagen_completa" && portadaImagenUrl
            ? `<section class="cc-cover-card cc-cover-card--image">
                <img src="${escapeHtml(normalizarRutaImagen(portadaImagenUrl))}" alt="">
                <div class="cc-cover-card__overlay">
                    ${logo}
                    ${portadaEtiqueta ? `<div class="cc-cover-card__label">${escapeHtml(portadaEtiqueta)}</div>` : ""}
                    ${titulo ? `<h3 class="cc-cover-card__title">${escapeHtml(titulo)}</h3>` : ""}
                    ${portadaDescripcion || subtitulo ? `<div class="cc-cover-card__desc">${escapeHtml(portadaDescripcion || subtitulo)}</div>` : ""}
                    ${contacto || portadaNota || cta ? contacto || `<div class="cc-cover-card__cta">${escapeHtml(portadaNota || cta)}</div>` : ""}
                </div>
            </section>`
            : `<section class="cc-cover-card">
                ${logo}
                ${portadaEtiqueta ? `<div class="cc-cover-card__label">${escapeHtml(portadaEtiqueta)}</div>` : ""}
                ${titulo ? `<h3 class="cc-cover-card__title">${escapeHtml(titulo)}</h3>` : ""}
                ${portadaDescripcion || subtitulo ? `<div class="cc-cover-card__desc">${escapeHtml(portadaDescripcion || subtitulo)}</div>` : ""}
                ${contacto || portadaNota || cta ? contacto || `<div class="cc-cover-card__cta">${escapeHtml(portadaNota || cta)}</div>` : ""}
            </section>`) : "";
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
        limpiarPreviewPaginasExportacion();
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
        limpiarPreviewPaginasExportacion();
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
        limpiarPreviewPaginasExportacion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: agregar todos los candidatos cargados por el filtro actual.
     * Impacto: UI Catalogos comerciales; permite usar una categoria como fuente masiva de productos.
     * Contrato: solo usa los candidatos ya consultados; respeta el limite de carga seleccionado y no escribe BD.
     */
    function seleccionarCargados() {
        estado.items.forEach((item) => {
            estado.seleccion.set(String(item.id_sku), item);
        });
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
        limpiarPreviewPaginasExportacion();
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
        limpiarPreviewPaginasExportacion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: quitar de la seleccion todos los candidatos cargados por el filtro actual.
     * Impacto: UI Catalogos comerciales; facilita corregir altas masivas por categoria.
     * Contrato: solo modifica seleccion local/localStorage; no borra catalogos ni SKUs.
     */
    function quitarCargados() {
        estado.items.forEach((item) => {
            estado.seleccion.delete(String(item.id_sku));
        });
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
        limpiarPreviewPaginasExportacion();
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
            if (mostrarPrecio) partes.push(precioTextoComercial(item));
            if (mostrarSku) partes.push(`SKU ${item.sku || ""}`.trim());
            if (mostrarDisponibilidad) partes.push(item.disponibilidad_simple || "consultar disponibilidad");
            lineas.push(partes.filter(Boolean).join(" - "));
            if (Array.isArray(item.variantes) && item.variantes.length) {
                item.variantes.slice(0, 12).forEach((variante) => {
                    lineas.push(`   - ${variante.etiqueta || variante.sku || "Variante"}`);
                });
            }
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
        if ($("cc_portada_tipo")) $("cc_portada_tipo").value = "plantilla";
        if ($("cc_portada_etiqueta")) $("cc_portada_etiqueta").value = "Catalogo recomendado";
        if ($("cc_portada_imagen_url")) $("cc_portada_imagen_url").value = "";
        if ($("cc_logo_url")) $("cc_logo_url").value = "";
        if ($("cc_contacto_texto")) $("cc_contacto_texto").value = "";
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
     * IA: Codex GPT-5 | Fecha: 2026-07-26
     * Proposito: guardar el armado actual como catalogo comercial persistente.
     * Impacto: UI Catalogos comerciales; escribe BD mediante endpoint protegido sin publicar enlaces.
     * Contrato: requiere nombre e items seleccionados; actualiza el catalogo cargado o crea uno nuevo.
     */
    async function guardarCatalogoServidor() {
        const nombre = ($("cc_borrador_nombre")?.value || "").trim();
        if (!nombre) throw new Error("Captura un nombre para el catalogo");
        if (!estado.seleccion.size) throw new Error("Selecciona al menos un producto para guardar");
        const botonGuardar = $("cc_guardar_borrador");
        const htmlOriginal = botonGuardar ? botonGuardar.innerHTML : "";
        if (botonGuardar) {
            botonGuardar.disabled = true;
            botonGuardar.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Guardando`;
        }
        guardarOpcionesLocal();
        setEstado("Guardando catalogo", "warning");
        try {
            const json = await apiPost("/catalogoerp/catalogos_comerciales_guardar", {
                id_catalogo_comercial: estado.catalogoActualId || 0,
                nombre,
                material: JSON.stringify(materialActual()),
                opciones: JSON.stringify(opcionesActuales()),
                items: JSON.stringify(Array.from(estado.seleccion.values()))
            });
            if (json.error) throw new Error(json.mensaje || "No se pudo guardar el catalogo");
            estado.catalogoActualId = Number(json.depurar && json.depurar.id_catalogo_comercial ? json.depurar.id_catalogo_comercial : 0);
            await cargarCatalogosGuardados();
            if ($("cc_borradores_guardados")) $("cc_borradores_guardados").value = String(estado.catalogoActualId || "");
            if (estado.catalogoActualId > 0 && window.history && window.history.replaceState) {
                window.history.replaceState({}, "", `/catalogoerp/catalogos_comerciales_editar?id_catalogo_comercial=${encodeURIComponent(estado.catalogoActualId)}`);
            }
            setEstado("Catalogo guardado", "success");
            await confirmarCatalogoGuardado();
        } finally {
            if (botonGuardar) {
                botonGuardar.disabled = false;
                botonGuardar.innerHTML = htmlOriginal || `<i class="bi bi-save"></i> Guardar`;
            }
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-08
     * Proposito: cargar portada/logo desde el editor y rellenar la ruta publica automaticamente.
     * Impacto: Catalogos comerciales; evita captura manual de URLs y mantiene los archivos fuera de imagenes de producto.
     * Contrato: sube un archivo al endpoint protegido; la ruta se persiste hasta que el usuario guarda el catalogo.
     */
    async function subirImagenMaterial(tipo) {
        const esLogo = tipo === "logo";
        const inputArchivo = $(esLogo ? "cc_logo_archivo" : "cc_portada_imagen_archivo");
        const inputRuta = $(esLogo ? "cc_logo_url" : "cc_portada_imagen_url");
        const boton = $(esLogo ? "cc_logo_subir" : "cc_portada_imagen_subir");
        if (!inputArchivo || !inputArchivo.files || !inputArchivo.files.length) {
            throw new Error(esLogo ? "Selecciona el archivo del logo" : "Selecciona la imagen de portada");
        }
        const original = boton ? boton.innerHTML : "";
        if (boton) {
            boton.disabled = true;
            boton.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Cargando`;
        }
        setEstado(esLogo ? "Cargando logo" : "Cargando portada", "warning");
        try {
            const data = new FormData();
            data.append("tipo_imagen", esLogo ? "logo" : "portada");
            data.append("id_catalogo_comercial", String(estado.catalogoActualId || 0));
            data.append("archivo_imagen", inputArchivo.files[0]);
            const json = await apiPostFormData("/catalogoerp/catalogos_comerciales_subir_imagen", data);
            if (json.error) throw new Error(json.mensaje || "No se pudo cargar la imagen");
            const ruta = json.depurar && json.depurar.url_imagen ? json.depurar.url_imagen : "";
            if (!ruta) throw new Error("La carga no devolvio una ruta publica");
            if (inputRuta) inputRuta.value = ruta;
            if (!esLogo && $("cc_portada_tipo")) $("cc_portada_tipo").value = "imagen_completa";
            inputArchivo.value = "";
            guardarMaterialLocal();
            renderSeleccion();
            limpiarPreviewPaginasExportacion();
            setEstado(esLogo ? "Logo cargado" : "Portada cargada", "success");
        } finally {
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = original || (esLogo ? `<i class="bi bi-cloud-arrow-up"></i> Cargar logo` : `<i class="bi bi-cloud-arrow-up"></i> Cargar portada`);
            }
        }
    }

    async function confirmarCatalogoGuardado() {
        if (!window.Swal) {
            alert("Catalogo guardado correctamente");
            return;
        }
        const resultado = await Swal.fire({
            title: "Catalogo guardado",
            text: "Puedes seguir editando este catalogo o volver a la lista.",
            icon: "success",
            showDenyButton: true,
            confirmButtonText: "Seguir editando",
            denyButtonText: "Ir a catalogos"
        });
        if (resultado.isDenied) {
            window.location.href = "/catalogoerp/catalogos_comerciales";
        }
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
        aplicarOpciones(borrador.opciones || {});
        if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = nombre;
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-26
     * Proposito: cargar un catalogo comercial persistido en el editor visual.
     * Impacto: UI Catalogos comerciales; recupera material, opciones e items desde BD.
     * Contrato: requiere catalogo seleccionado; no modifica productos ni publica el material.
     */
    async function cargarCatalogoServidor(idCatalogo) {
        const id = Number(idCatalogo || $("cc_borradores_guardados")?.value || 0);
        if (!id) throw new Error("Selecciona un catalogo para cargar");
        const json = await apiGet(`/catalogoerp/catalogos_comerciales_consultar?id_catalogo_comercial=${encodeURIComponent(id)}`);
        if (json.error) throw new Error(json.mensaje || "No se pudo cargar el catalogo");
        const depurar = json.depurar || {};
        estado.catalogoActualId = id;
        estado.seleccion.clear();
        (Array.isArray(depurar.items) ? depurar.items : []).forEach((item) => {
            if (item && item.id_sku) estado.seleccion.set(String(item.id_sku), item);
        });
        aplicarMaterial(depurar.material || {});
        aplicarOpciones(depurar.opciones || {});
        if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = depurar.catalogo && depurar.catalogo.nombre ? depurar.catalogo.nombre : "";
        guardarSeleccionLocal();
        renderTabla();
        renderSeleccion();
        setEstado("Catalogo cargado", "success");
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-26
     * Proposito: archivar un catalogo comercial persistido sin borrarlo fisicamente.
     * Impacto: UI Catalogos comerciales; limpia el selector de trabajo y conserva trazabilidad en BD.
     * Contrato: requiere catalogo seleccionado y llama `catalogos_comerciales_archivar`.
     */
    async function archivarCatalogoServidor(idCatalogo) {
        const id = Number(idCatalogo || $("cc_borradores_guardados")?.value || estado.catalogoActualId || 0);
        if (!id) throw new Error("Selecciona un catalogo para archivar");
        const json = await apiPost("/catalogoerp/catalogos_comerciales_archivar", { id_catalogo_comercial: id });
        if (json.error) throw new Error(json.mensaje || "No se pudo archivar el catalogo");
        if (estado.catalogoActualId === id) {
            estado.catalogoActualId = 0;
            if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = "";
        }
        await cargarCatalogosGuardados();
        setEstado("Catalogo archivado", "success");
    }

    function nombreArchivoSeguro(nombre) {
        return String(nombre || "catalogo-comercial")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-zA-Z0-9_-]+/g, "-")
            .replace(/^-+|-+$/g, "")
            .toLowerCase() || "catalogo-comercial";
    }
    function estilosBasicosExportacion(elemento) {
        const estilos = window.getComputedStyle(elemento);
        const propiedades = [
            "background", "background-color", "border", "border-radius", "box-sizing", "color", "display", "flex", "flex-direction",
            "font-family", "font-size", "font-weight", "gap", "grid-template-columns", "height", "justify-content", "line-height", "margin",
            "max-width", "min-height", "object-fit", "overflow", "padding", "text-align", "text-transform", "width"
        ];
        propiedades.forEach((propiedad) => {
            elemento.style.setProperty(propiedad, estilos.getPropertyValue(propiedad));
        });
        if (estilos.aspectRatio && estilos.aspectRatio !== "auto") elemento.style.aspectRatio = estilos.aspectRatio;
    }

    async function imagenComoDataUrl(src) {
        const url = normalizarRutaImagen(src);
        if (!url || /^data:/i.test(url)) return url;
        const response = await fetch(url, { credentials: "same-origin" });
        if (!response.ok) throw new Error("No se pudo preparar una imagen para el PNG");
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(new Error("No se pudo leer una imagen para el PNG"));
            reader.readAsDataURL(blob);
        });
    }

    async function clonarSuperficieExportacion(origen) {
        const clon = origen.cloneNode(true);
        clon.classList.add("cc-export-surface");
        if (clon.firstElementChild && clon.firstElementChild.classList.contains("d-flex")) clon.firstElementChild.remove();
        clon.querySelectorAll(".cc-preview-toolbar").forEach((el) => el.remove());
        const originales = [origen, ...origen.querySelectorAll("*")];
        const clones = [clon, ...clon.querySelectorAll("*")];
        clones.forEach((elemento, index) => {
            estilosBasicosExportacion(elemento);
            const original = originales[index];
            if (original && original.tagName === "IMG") {
                elemento.setAttribute("width", original.naturalWidth || original.clientWidth || 300);
                elemento.setAttribute("height", original.naturalHeight || original.clientHeight || 300);
            }
        });
        const imagenes = Array.from(clon.querySelectorAll("img"));
        await Promise.all(imagenes.map(async (img) => {
            try {
                img.src = await imagenComoDataUrl(img.getAttribute("src") || "");
            } catch (e) {
                img.removeAttribute("src");
            }
        }));
        return clon;
    }

    function svgExportacion(clon, ancho, alto) {
        const html = new XMLSerializer().serializeToString(clon);
        return `<svg xmlns="http://www.w3.org/2000/svg" width="${ancho}" height="${alto}" viewBox="0 0 ${ancho} ${alto}"><foreignObject width="100%" height="100%">${html}</foreignObject></svg>`;
    }

    function descargarBlob(blob, nombre) {
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement("a");
        enlace.href = url;
        enlace.download = nombre;
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        URL.revokeObjectURL(url);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-26
     * Proposito: exportar la vista previa del catalogo comercial como PNG descargable para redes sociales y WhatsApp.
     * Impacto: UI Catalogos comerciales; genera la imagen en el navegador sin guardar archivos ni publicar enlaces.
     * Contrato: requiere items seleccionados; convierte imagenes same-origin a data URL y descarga un PNG del area `.cc-print-area`.
     */
    async function exportarPreviewPng() {
        if (!estado.seleccion.size) throw new Error("Selecciona al menos un producto para exportar PNG");
        const superficie = document.querySelector(".cc-print-area");
        if (!superficie) throw new Error("No se encontro la vista previa para exportar");
        setEstado("Preparando PNG", "warning");
        const rect = superficie.getBoundingClientRect();
        const ancho = Math.max(900, Math.ceil(rect.width || superficie.scrollWidth || 900));
        const alto = Math.max(400, Math.ceil(superficie.scrollHeight || rect.height || 400));
        const clon = await clonarSuperficieExportacion(superficie);
        clon.setAttribute("xmlns", "http://www.w3.org/1999/xhtml");
        clon.style.width = `${ancho}px`;
        clon.style.minHeight = `${alto}px`;
        clon.style.padding = "24px";
        clon.style.background = "#ffffff";
        const svg = svgExportacion(clon, ancho, alto);
        const svgUrl = URL.createObjectURL(new Blob([svg], { type: "image/svg+xml;charset=utf-8" }));
        const imagen = new Image();
        const escala = Math.min(2, window.devicePixelRatio || 1.5);
        await new Promise((resolve, reject) => {
            imagen.onload = resolve;
            imagen.onerror = () => reject(new Error("No se pudo generar el PNG desde la vista previa"));
            imagen.src = svgUrl;
        });
        const canvas = document.createElement("canvas");
        canvas.width = Math.ceil(ancho * escala);
        canvas.height = Math.ceil(alto * escala);
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.scale(escala, escala);
        ctx.drawImage(imagen, 0, 0);
        URL.revokeObjectURL(svgUrl);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/png", 0.95));
        if (!blob) throw new Error("No se pudo descargar el PNG");
        const nombre = nombreArchivoSeguro($("cc_borrador_nombre")?.value || $("cc_material_titulo")?.value || "catalogo-comercial");
        descargarBlob(blob, `${nombre}.png`);
        setEstado("PNG exportado", "success");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: exportar el armado actual como JSON portable.
     * Impacto: UI Catalogos comerciales; permite respaldar o compartir una propuesta sin publicar enlaces.
     * Contrato: exporta material, opciones y seleccion actual; no escribe BD.
     */
    function exportarBorradorNombrado() {
        const nombre = ($("cc_borrador_nombre")?.value || "").trim() || "catalogo-comercial";
        if (!estado.seleccion.size) throw new Error("Selecciona al menos un producto para exportar");
        const borrador = {
            id_catalogo_comercial: estado.catalogoActualId || 0,
            nombre,
            fecha: new Date().toISOString(),
            material: materialActual(),
            opciones: opcionesActuales(),
            seleccion: Array.from(estado.seleccion.values())
        };
        const payload = {
            formato: "erp_catalogo_comercial_borrador_local",
            version: 2,
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
            opciones: borrador.opciones || {},
            seleccion
        };
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: importar un armado desde JSON al editor actual.
     * Impacto: UI Catalogos comerciales; permite recuperar propuestas portables y luego guardarlas en BD.
     * Contrato: valida formato basico, carga en pantalla y no escribe BD hasta presionar Guardar.
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
            estado.catalogoActualId = 0;
            estado.seleccion.clear();
            (borrador.seleccion || []).forEach((item) => {
                if (item && item.id_sku) estado.seleccion.set(String(item.id_sku), item);
            });
            aplicarMaterial(borrador.material || {});
            aplicarOpciones(borrador.opciones || {});
            guardarSeleccionLocal();
            renderTabla();
            renderSeleccion();
            if ($("cc_borradores_guardados")) $("cc_borradores_guardados").value = "";
            if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = borrador.nombre;
            return true;
        });
    }


    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-27
     * Proposito: separar el flujo de Catalogos comerciales en vistas operativas dentro de la misma pantalla.
     * Impacto: UI Catalogos comerciales; permite alternar entre Editor, Guardados y Vista previa sin recargar pagina.
     * Contrato: solo muestra/oculta secciones `data-cc-view`; no modifica BD ni seleccion.
     */
    function mostrarVista(nombre) {
        const vista = ["editor", "guardados", "preview"].includes(nombre) ? nombre : "editor";
        document.querySelectorAll("[data-cc-view]").forEach((section) => {
            section.classList.toggle("d-none", section.getAttribute("data-cc-view") !== vista);
        });
        document.querySelectorAll("[data-cc-view-button]").forEach((boton) => {
            const activo = boton.getAttribute("data-cc-view-button") === vista;
            boton.className = activo ? "btn btn-sm btn-primary" : "btn btn-sm btn-light-primary";
        });
        if (vista === "preview") renderSeleccion();
    }

    function fechaCatalogoTexto(valor) {
        if (!valor) return "Sin fecha";
        const fecha = new Date(String(valor).replace(" ", "T"));
        if (Number.isNaN(fecha.getTime())) return String(valor);
        return fecha.toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-27
     * Proposito: mostrar catalogos comerciales guardados como una vista propia y accionable.
     * Impacto: UI Catalogos comerciales; evita depender solo del selector compacto para editar o archivar.
     * Contrato: lee `estado.catalogos`; las acciones llaman consultar/archivar existentes.
     */
    function renderCatalogosGuardadosLista() {
        const contenedor = $("cc_catalogos_guardados_lista");
        if (!contenedor) return;
        if (!estado.catalogos.length) {
            contenedor.innerHTML = `<div class="text-muted py-4">No hay catalogos comerciales guardados. Usa Nuevo para preparar el primero.</div>`;
            return;
        }
        contenedor.innerHTML = estado.catalogos.map((catalogo) => `<article class="cc-saved-card">
            <div>
                <div class="cc-saved-card__title">${escapeHtml(catalogo.nombre || "Catalogo sin nombre")}</div>
                <div class="cc-saved-card__meta">${escapeHtml(catalogo.codigo || "")} - ${escapeHtml(catalogo.total_items || 0)} productos</div>
                <div class="cc-saved-card__meta">Actualizado: ${escapeHtml(fechaCatalogoTexto(catalogo.fecha_actualizacion || catalogo.fecha_creacion))}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-auto">
                <a class="btn btn-sm btn-light-dark" href="/catalogoerp/catalogos_comerciales_ver?id_catalogo_comercial=${encodeURIComponent(catalogo.id_catalogo_comercial)}"><i class="bi bi-eye"></i> Ver</a>
                <a class="btn btn-sm btn-light-primary" href="/catalogoerp/catalogos_comerciales_editar?id_catalogo_comercial=${encodeURIComponent(catalogo.id_catalogo_comercial)}"><i class="bi bi-pencil"></i> Editar</a>
                <button class="btn btn-sm btn-light-danger" type="button" data-cc-archivar-catalogo="${escapeHtml(catalogo.id_catalogo_comercial)}"><i class="bi bi-archive"></i> Archivar</button>
            </div>
        </article>`).join("");
    }

    function redondearRect(ctx, x, y, w, h, r) {
        const radio = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + radio, y);
        ctx.lineTo(x + w - radio, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + radio);
        ctx.lineTo(x + w, y + h - radio);
        ctx.quadraticCurveTo(x + w, y + h, x + w - radio, y + h);
        ctx.lineTo(x + radio, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - radio);
        ctx.lineTo(x, y + radio);
        ctx.quadraticCurveTo(x, y, x + radio, y);
        ctx.closePath();
    }

    function canvasLineas(ctx, texto, maxWidth) {
        const palabras = String(texto || "").split(/\s+/).filter(Boolean);
        const lineas = [];
        let actual = "";
        palabras.forEach((palabra) => {
            const prueba = actual ? `${actual} ${palabra}` : palabra;
            if (ctx.measureText(prueba).width <= maxWidth || !actual) {
                actual = prueba;
                return;
            }
            lineas.push(actual);
            actual = palabra;
        });
        if (actual) lineas.push(actual);
        return lineas;
    }

    function canvasTexto(ctx, texto, x, y, maxWidth, lineHeight, maxLineas) {
        const lineas = canvasLineas(ctx, texto, maxWidth).slice(0, maxLineas || 3);
        lineas.forEach((linea, index) => {
            const final = index === lineas.length - 1 && canvasLineas(ctx, texto, maxWidth).length > lineas.length ? `${linea.replace(/\s+\S+$/, "")}...` : linea;
            ctx.fillText(final, x, y + index * lineHeight);
        });
        return y + lineas.length * lineHeight;
    }

    async function cargarImagenCanvas(url) {
        let ruta = normalizarRutaImagen(url);
        if (!ruta) return null;
        if (/^https?:\/\//i.test(ruta)) {
            try {
                const absoluta = new URL(ruta);
                if (absoluta.origin !== window.location.origin) return null;
                ruta = `${absoluta.pathname}${absoluta.search}`;
            } catch (e) {
                return null;
            }
        }
        try {
            const response = await fetch(ruta, { credentials: "same-origin" });
            if (!response.ok) return null;
            const blob = await response.blob();
            const dataUrl = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
            return await new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = () => resolve(null);
                img.src = dataUrl;
            });
        } catch (e) {
            return null;
        }
    }

    function dibujarImagenCover(ctx, img, x, y, w, h) {
        if (!img) {
            ctx.fillStyle = "#f1f3f6";
            ctx.fillRect(x, y, w, h);
            ctx.fillStyle = "#9ca3af";
            ctx.font = "700 24px Arial, sans-serif";
            ctx.textAlign = "center";
            ctx.fillText("Sin imagen", x + w / 2, y + h / 2);
            ctx.textAlign = "left";
            return;
        }
        const escala = Math.max(w / img.width, h / img.height);
        const sw = w / escala;
        const sh = h / escala;
        const sx = (img.width - sw) / 2;
        const sy = (img.height - sh) / 2;
        ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    function dibujarImagenContain(ctx, img, x, y, w, h) {
        if (!img) {
            dibujarImagenCover(ctx, img, x, y, w, h);
            return;
        }
        const escala = Math.min(w / img.width, h / img.height);
        const dw = img.width * escala;
        const dh = img.height * escala;
        const dx = x + (w - dw) / 2;
        const dy = y + (h - dh) / 2;
        ctx.drawImage(img, dx, dy, dw, dh);
    }

    function dibujarImagenCuadrada(ctx, img, x, y, size) {
        ctx.save();
        ctx.fillStyle = "#f8fafc";
        redondearRect(ctx, x, y, size, size, 8);
        ctx.fill();
        ctx.strokeStyle = "#dfe3ea";
        ctx.lineWidth = 1;
        ctx.stroke();
        redondearRect(ctx, x + 2, y + 2, size - 4, size - 4, 6);
        ctx.clip();
        dibujarImagenContain(ctx, img, x + 4, y + 4, size - 8, size - 8);
        ctx.restore();
    }

    async function dibujarTarjetaCanvas(ctx, item, x, y, w, h, opciones) {
        const estilo = opciones.estiloVisual || estiloVisualActual();
        ctx.save();
        ctx.fillStyle = "#ffffff";
        redondearRect(ctx, x, y, w, h, 18);
        ctx.fill();
        ctx.strokeStyle = "#dfe3ea";
        ctx.lineWidth = 2;
        ctx.stroke();
        const altoImagen = Array.isArray(item.variantes) && item.variantes.length
            ? Math.min(Math.round(w * 0.9), Math.round(h * 0.58))
            : Math.min(w, Math.round(h * 0.66));
        ctx.fillStyle = "#f8fafc";
        redondearRect(ctx, x, y, w, altoImagen, 18);
        ctx.fill();
        redondearRect(ctx, x, y, w, altoImagen, 18);
        ctx.clip();
        const img = await cargarImagenCanvas(item.imagen_portada);
        dibujarImagenContain(ctx, img, x + 8, y + 8, w - 16, altoImagen - 16);
        ctx.restore();

        const bodyY = y + altoImagen + 12;
        ctx.fillStyle = estilo.colorProducto || "#181c32";
        ctx.font = `800 ${estilo.tamProducto || 11}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        let cursor = canvasTexto(ctx, item.nombre || "Producto", x + 10, bodyY, w - 20, Math.max(13, Number(estilo.tamProducto || 11) + 4), 3) + 1;
        ctx.fillStyle = estilo.colorMeta || "#5e6278";
        ctx.font = `500 ${estilo.tamMeta || 9}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        const metas = [];
        if (opciones.mostrarMarca && item.marca) metas.push(item.marca);
        if (opciones.mostrarCategoria) metas.push(item.categoria || "Sin categoria");
        if (opciones.mostrarPresentacion && item.presentacion_comercial) metas.push(item.presentacion_comercial);
        if (opciones.mostrarSku) metas.push(item.sku || "");
        if (opciones.mostrarDisponibilidad) metas.push(item.disponibilidad_simple || "consultar disponibilidad");
        const precioY = y + h - 18;
        metas.slice(0, 3).forEach((meta) => {
            if (cursor > precioY - 26) return;
            cursor = canvasTexto(ctx, meta, x + 10, cursor, w - 20, Math.max(10, Number(estilo.tamMeta || 9) + 2), 1);
        });
        if (Array.isArray(item.variantes) && item.variantes.length) {
            const variantesConImagen = item.variantes.filter((variante) => variante.imagen_portada).slice(0, 5);
            if (variantesConImagen.length && cursor <= precioY - 58) {
                const gapMini = 6;
                const miniSize = Math.min(48, Math.max(34, Math.floor((w - 20 - gapMini * (variantesConImagen.length - 1)) / variantesConImagen.length)));
                for (let i = 0; i < variantesConImagen.length; i += 1) {
                    const imgVariante = await cargarImagenCanvas(variantesConImagen[i].imagen_portada);
                    dibujarImagenCuadrada(ctx, imgVariante, x + 10 + i * (miniSize + gapMini), cursor, miniSize);
                }
                cursor += miniSize + 8;
            }
            ctx.fillStyle = estilo.colorMeta || "#5e6278";
            ctx.font = `700 ${estilo.tamMeta || 9}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
            item.variantes.slice(0, 6).forEach((variante) => {
                if (cursor > precioY - 24) return;
                cursor = canvasTexto(ctx, etiquetaValorVariante(variante.etiqueta || variante.sku || "Variante"), x + 10, cursor, w - 20, Math.max(10, Number(estilo.tamMeta || 9) + 2), 1);
            });
            if (item.variantes.length > 6 && cursor <= precioY - 24) {
                cursor = canvasTexto(ctx, `+ ${item.variantes.length - 6} variantes mas`, x + 10, cursor, w - 20, Math.max(10, Number(estilo.tamMeta || 9) + 2), 1);
            }
        }
        if (opciones.mostrarPrecio) {
            ctx.fillStyle = estilo.colorPrecio || "#0f7a5f";
            ctx.font = `800 ${estilo.tamPrecio || 13}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
            ctx.fillText(precioTextoComercial(item), x + 10, precioY);
        }
    }

    /*
     * IA: Codex GPT-5 | Fecha: 2026-08-02
     * Proposito: definir paginas PNG densas para catalogos comerciales compartibles por redes.
     * Impacto: solo cambia distribucion visual de exportacion; no modifica datos, precios ni persistencia.
     * Contrato: mantiene lienzo vertical 1080x1600; prueba visual con 5 columnas, 4 filas con portada y 5 filas despues.
     */
    function layoutPaginaCatalogo(plantilla) {
        const width = 1080;
        const height = 1600;
        const margen = 36;
        const gap = 12;
        if (plantilla === "compact") {
            return { width, height, margen, gap, columnas: 1, cardW: width - margen * 2, cardH: 155, headerH: 76, tituloH: 52, portadaH: 120 };
        }
        const columnas = columnasExportacionActual();
        const filasDeseadas = filasExportacionActual();
        const altoPorColumnas = { 2: 470, 3: 360, 4: 305, 5: 270 };
        const cardW = Math.floor((width - margen * 2 - gap * (columnas - 1)) / columnas);
        return { width, height, margen, gap, columnas, filasDeseadas, cardW, cardH: altoPorColumnas[columnas] || 360, headerH: 76, tituloH: 52, portadaH: 120 };
    }

    function altoDisponibleItems(layout, incluirPortada) {
        const encabezado = incluirPortada ? layout.headerH : layout.tituloH;
        const altoInicial = layout.margen + encabezado + (incluirPortada ? layout.portadaH + layout.gap : 0) + layout.gap + 30;
        return Math.max(120, layout.height - altoInicial - layout.margen);
    }

    function cardHPaginaCatalogo(layout, incluirPortada) {
        if (layout.columnas <= 1 || !layout.filasDeseadas) return layout.cardH;
        const disponible = altoDisponibleItems(layout, incluirPortada);
        const filas = Math.max(1, layout.filasDeseadas);
        return Math.max(170, Math.floor((disponible - layout.gap * (filas - 1)) / filas));
    }

    function itemsPorPaginaCatalogo(layout, incluirPortada) {
        const cardH = cardHPaginaCatalogo(layout, incluirPortada);
        const disponible = altoDisponibleItems(layout, incluirPortada);
        const filasCalculadas = Math.max(1, Math.floor((disponible + layout.gap) / (cardH + layout.gap)));
        const limiteFilas = layout.filasDeseadas || (layout.columnas > 1 ? (incluirPortada ? 4 : 5) : filasCalculadas);
        const filas = Math.min(limiteFilas, filasCalculadas);
        return Math.max(1, filas * layout.columnas);
    }

    function paginasCatalogo(items, layout, portadaActiva) {
        const paginas = [];
        let inicio = 0;
        if (portadaActiva) {
            paginas.push({ items: [], portada: true, portadaCompleta: true });
        }
        while (inicio < items.length) {
            const incluirPortada = false;
            const cantidad = itemsPorPaginaCatalogo(layout, incluirPortada);
            paginas.push({ items: items.slice(inicio, inicio + cantidad), portada: incluirPortada });
            inicio += cantidad;
        }
        return paginas.length ? paginas : [{ items: [], portada: portadaActiva }];
    }

    async function dibujarPortadaCompletaPagina(ctx, material, layout, opciones) {
        const estilo = opciones.estiloVisual || estiloVisualActual();
        const fondo = await cargarImagenCanvas(material.portadaImagenUrl || "");
        if (fondo) {
            dibujarImagenCover(ctx, fondo, 0, 0, layout.width, layout.height);
            const gradiente = ctx.createLinearGradient(0, layout.height * 0.42, 0, layout.height);
            gradiente.addColorStop(0, "rgba(17,24,39,0)");
            gradiente.addColorStop(1, "rgba(17,24,39,.88)");
            ctx.fillStyle = gradiente;
            ctx.fillRect(0, 0, layout.width, layout.height);
        } else {
            ctx.fillStyle = "#f8fafc";
            ctx.fillRect(0, 0, layout.width, layout.height);
            ctx.fillStyle = estilo.colorPrecio || "#0f7a5f";
            ctx.fillRect(0, 0, layout.width, 18);
            ctx.fillStyle = "#ffffff";
            redondearRect(ctx, layout.margen, layout.margen, layout.width - layout.margen * 2, layout.height - layout.margen * 2, 28);
            ctx.fill();
            ctx.strokeStyle = "#dfe3ea";
            ctx.lineWidth = 3;
            ctx.stroke();
            ctx.strokeStyle = "#e7e9ef";
            ctx.lineWidth = 2;
            ctx.setLineDash([10, 10]);
            redondearRect(ctx, layout.margen + 34, layout.margen + 34, layout.width - layout.margen * 2 - 68, Math.round(layout.height * 0.45), 18);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = estilo.colorMeta || "#5e6278";
            ctx.font = `800 22px ${estilo.familiaFuente || "Arial, sans-serif"}`;
            ctx.textAlign = "center";
            ctx.fillText("Espacio para imagen principal, logo o identidad visual", layout.width / 2, layout.margen + Math.round(layout.height * 0.24));
            ctx.textAlign = "left";
        }
        const logo = await cargarImagenCanvas(material.logoUrl || "");
        const baseX = layout.margen + 34;
        const baseY = fondo ? Math.round(layout.height * 0.58) : Math.round(layout.height * 0.57);
        if (logo) dibujarImagenCuadrada(ctx, logo, baseX, baseY - 92, 76);
        ctx.fillStyle = fondo ? "#ffffff" : (estilo.colorPrecio || "#0f7a5f");
        ctx.font = `900 18px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        canvasTexto(ctx, (material.portadaEtiqueta || "Catalogo recomendado").toUpperCase(), baseX, baseY + (logo ? 0 : -24), layout.width - layout.margen * 2 - 68, 22, 1);
        ctx.fillStyle = fondo ? "#ffffff" : (estilo.colorTitulo || "#181c32");
        ctx.font = `900 ${Math.max(40, Number(estilo.tamTitulo || 23) + 24)}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        let yTexto = canvasTexto(ctx, material.titulo || "Catalogo de productos", baseX, baseY + 54, layout.width - layout.margen * 2 - 68, Math.max(48, Number(estilo.tamTitulo || 23) + 30), 2);
        ctx.fillStyle = fondo ? "rgba(255,255,255,.9)" : (estilo.colorMeta || "#5e6278");
        ctx.font = `600 24px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        yTexto = canvasTexto(ctx, material.portadaDescripcion || material.subtitulo || "", baseX, yTexto + 14, layout.width - layout.margen * 2 - 68, 30, 3);
        ctx.fillStyle = fondo ? "#ffffff" : (estilo.colorPrecio || "#0f7a5f");
        ctx.font = `900 22px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        canvasTexto(ctx, material.contactoTexto || material.portadaNota || material.cta || "", baseX, yTexto + 28, layout.width - layout.margen * 2 - 68, 28, 2);
    }

    function dibujarPortadaPagina(ctx, material, layout, y, opciones) {
        const estilo = opciones.estiloVisual || estiloVisualActual();
        ctx.fillStyle = "#f8fafc";
        redondearRect(ctx, layout.margen, y, layout.width - layout.margen * 2, layout.portadaH, 20);
        ctx.fill();
        ctx.strokeStyle = "#dfe3ea";
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.fillStyle = "#0f7a5f";
        ctx.font = `800 14px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        ctx.fillText((material.portadaEtiqueta || "Catalogo recomendado").toUpperCase(), layout.margen + 22, y + 28);
        if (material.contactoTexto || material.portadaNota || material.cta) {
            ctx.textAlign = "right";
            ctx.fillText(material.contactoTexto || material.portadaNota || material.cta, layout.width - layout.margen - 22, y + 28);
            ctx.textAlign = "left";
        }
        ctx.fillStyle = estilo.colorTitulo || "#181c32";
        ctx.font = `900 ${Math.max(24, Number(estilo.tamTitulo || 23) + 7)}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        canvasTexto(ctx, material.titulo || "Catalogo de productos", layout.margen + 22, y + 62, layout.width - layout.margen * 2 - 44, Math.max(28, Number(estilo.tamTitulo || 23) + 11), 1);
        ctx.fillStyle = estilo.colorMeta || "#5e6278";
        ctx.font = `500 16px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        canvasTexto(ctx, material.portadaDescripcion || material.subtitulo || "", layout.margen + 22, y + 92, layout.width - layout.margen * 2 - 44, 20, 1);
        return y + layout.portadaH + layout.gap;
    }

    function dibujarHeaderPagina(ctx, material, layout, y, pagina, totalPaginas, compacto, opciones) {
        const estilo = opciones.estiloVisual || estiloVisualActual();
        ctx.fillStyle = "#ffffff";
        const alto = compacto ? layout.tituloH : layout.headerH;
        redondearRect(ctx, layout.margen, y, layout.width - layout.margen * 2, alto, 18);
        ctx.fill();
        ctx.strokeStyle = "#dfe3ea";
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.fillStyle = estilo.colorTitulo || "#181c32";
        ctx.font = `900 ${compacto ? Math.max(20, Number(estilo.tamTitulo || 23) - 1) : estilo.tamTitulo || 23}px ${estilo.familiaFuente || "Arial, sans-serif"}`;
        canvasTexto(ctx, material.titulo || "Catalogo de productos", layout.margen + 22, y + (compacto ? 34 : 32), layout.width - layout.margen * 2 - 145, Math.max(24, Number(estilo.tamTitulo || 23) + 4), 1);
        if (!compacto) {
            ctx.fillStyle = estilo.colorMeta || "#5e6278";
            ctx.font = `500 15px ${estilo.familiaFuente || "Arial, sans-serif"}`;
            canvasTexto(ctx, material.subtitulo || "", layout.margen + 22, y + 52, layout.width - layout.margen * 2 - 44, 19, 1);
            ctx.fillStyle = estilo.colorPrecio || "#0f7a5f";
            ctx.font = `800 14px ${estilo.familiaFuente || "Arial, sans-serif"}`;
            canvasTexto(ctx, material.cta || "", layout.margen + 22, y + 70, layout.width - layout.margen * 2 - 44, 18, 1);
        }
        ctx.fillStyle = "#7e8299";
        ctx.font = "800 14px Arial, sans-serif";
        ctx.textAlign = "right";
        ctx.fillText(`Pagina ${pagina} de ${totalPaginas}`, layout.width - layout.margen - 22, y + 32);
        ctx.textAlign = "left";
        return y + alto + layout.gap;
    }

    async function dibujarPaginaCatalogoCanvas(paginaDatos, numeroPagina, totalPaginas, layout, material, opciones) {
        const canvas = document.createElement("canvas");
        canvas.width = layout.width;
        canvas.height = layout.height;
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, layout.width, layout.height);
        let y = layout.margen;
        if (paginaDatos.portadaCompleta) {
            await dibujarPortadaCompletaPagina(ctx, material, layout, opciones);
            return canvas;
        }
        if (paginaDatos.portada) y = dibujarPortadaPagina(ctx, material, layout, y, opciones);
        y = dibujarHeaderPagina(ctx, material, layout, y, numeroPagina, totalPaginas, !paginaDatos.portada, opciones);
        const cardH = cardHPaginaCatalogo(layout, paginaDatos.portada);
        for (let i = 0; i < paginaDatos.items.length; i += 1) {
            const col = i % layout.columnas;
            const row = Math.floor(i / layout.columnas);
            const x = layout.margen + col * (layout.cardW + layout.gap);
            const itemY = y + row * (cardH + layout.gap);
            await dibujarTarjetaCanvas(ctx, paginaDatos.items[i], x, itemY, layout.cardW, cardH, opciones);
        }
        ctx.fillStyle = "#9ca3af";
        ctx.font = "600 16px Arial, sans-serif";
        ctx.textAlign = "center";
        ctx.fillText("Precios y disponibilidad sujetos a confirmacion", layout.width / 2, layout.height - 26);
        ctx.textAlign = "left";
        return canvas;
    }

    function limpiarPreviewPaginasExportacion() {
        const wrap = $("cc_preview_paginas_wrap");
        const contenedor = $("cc_preview_paginas");
        const resumen = $("cc_preview_paginas_resumen");
        if (wrap) wrap.classList.add("d-none");
        if (contenedor) contenedor.innerHTML = "";
        if (resumen) resumen.textContent = "";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: previsualizar las paginas PNG antes de descargar archivos.
     * Impacto: UI Catalogos comerciales; permite ajustar filas/columnas sin generar imagenes innecesarias.
     * Contrato: dibuja miniaturas locales en canvas usando la misma rutina del exportador; no escribe servidor ni descarga.
     */
    async function previsualizarPaginasPngCanvas() {
        const items = itemsVisualesCatalogo();
        if (!items.length) throw new Error("Selecciona al menos un producto para previsualizar paginas");
        const wrap = $("cc_preview_paginas_wrap");
        const contenedor = $("cc_preview_paginas");
        const resumen = $("cc_preview_paginas_resumen");
        if (!wrap || !contenedor) return;
        setEstado("Preparando preview", "warning");
        contenedor.innerHTML = "";
        wrap.classList.remove("d-none");
        const opciones = opcionesActuales();
        const material = materialActual();
        const layout = layoutPaginaCatalogo(plantillaActual());
        const paginas = paginasCatalogo(items, layout, material.portadaActiva !== false);
        if (resumen) {
            const filas = layout.filasDeseadas ? `${layout.filasDeseadas}` : "auto";
            resumen.textContent = `${paginas.length} paginas - ${layout.columnas} columnas x ${filas} filas`;
        }
        for (let i = 0; i < paginas.length; i += 1) {
            setEstado(`Preview ${i + 1}/${paginas.length}`, "warning");
            const canvas = await dibujarPaginaCatalogoCanvas(paginas[i], i + 1, paginas.length, layout, material, opciones);
            const card = document.createElement("article");
            card.className = "cc-page-preview-card";
            const etiquetaPagina = paginas[i].portadaCompleta ? "Portada" : `${paginas[i].items.length} productos`;
            card.innerHTML = `<div class="cc-page-preview-card__meta"><span>Pagina ${i + 1}</span><span>${etiquetaPagina}</span></div>`;
            card.appendChild(canvas);
            contenedor.appendChild(card);
            await new Promise((resolve) => setTimeout(resolve, 40));
        }
        setEstado(`Preview listo (${paginas.length})`, "success");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-27
     * Proposito: generar catalogo comercial en paginas PNG de altura controlada para WhatsApp/redes.
     * Impacto: UI Catalogos comerciales; descarga imagenes numeradas sin una tira vertical excesiva.
     * Contrato: pagina a 1080x1400 px, dibuja solo datos seleccionados y omite imagenes externas no locales.
     */
    async function exportarPreviewPngCanvas() {
        const items = itemsVisualesCatalogo();
        if (!items.length) throw new Error("Selecciona al menos un producto para exportar PNG");
        setEstado("Preparando paginas PNG", "warning");
        const opciones = opcionesActuales();
        const material = materialActual();
        const layout = layoutPaginaCatalogo(plantillaActual());
        const paginas = paginasCatalogo(items, layout, material.portadaActiva !== false);
        const nombreBase = nombreArchivoSeguro($("cc_borrador_nombre")?.value || material.titulo || "catalogo-comercial");
        for (let i = 0; i < paginas.length; i += 1) {
            setEstado(`PNG ${i + 1}/${paginas.length}`, "warning");
            const canvas = await dibujarPaginaCatalogoCanvas(paginas[i], i + 1, paginas.length, layout, material, opciones);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/png", 0.95));
            if (!blob) throw new Error("No se pudo descargar una pagina PNG");
            const sufijo = paginas[i].portadaCompleta ? "portada" : `pag-${String(i + 1).padStart(2, "0")}`;
            descargarBlob(blob, `${nombreBase}-${sufijo}.png`);
            await new Promise((resolve) => setTimeout(resolve, 180));
        }
        setEstado(`PNG exportado (${paginas.length})`, "success");
    }
    function enlazarEventos() {
        $("cc_buscar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_recargar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_guardados_recargar")?.addEventListener("click", () => cargarCatalogosGuardados().catch(mostrarError));
        document.querySelectorAll("[data-cc-view-button]").forEach((boton) => {
            boton.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();
                mostrarVista(boton.getAttribute("data-cc-view-button"));
            });
        });
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
        $("cc_seleccionar_cargados")?.addEventListener("click", seleccionarCargados);
        $("cc_quitar_visibles")?.addEventListener("click", quitarVisibles);
        $("cc_quitar_cargados")?.addEventListener("click", quitarCargados);
        $("cc_previsualizar_paginas")?.addEventListener("click", () => {
            previsualizarPaginasPngCanvas().catch(mostrarError);
        });
        $("cc_exportar_png")?.addEventListener("click", () => {
            exportarPreviewPngCanvas().catch(mostrarError);
        });
        $("cc_modo_captura")?.addEventListener("click", alternarModoCaptura);
        $("cc_copiar_listado")?.addEventListener("click", () => {
            copiarListado()
                .then(() => setEstado("Listado copiado", "success"))
                .catch(mostrarError);
        });
        $("cc_guardar_borrador")?.addEventListener("click", () => {
            guardarCatalogoServidor().catch(mostrarError);
        });
        $("cc_portada_imagen_subir")?.addEventListener("click", () => {
            subirImagenMaterial("portada").catch(mostrarError);
        });
        $("cc_logo_subir")?.addEventListener("click", () => {
            subirImagenMaterial("logo").catch(mostrarError);
        });
        $("cc_cargar_borrador")?.addEventListener("click", () => {
            cargarCatalogoServidor().catch(mostrarError);
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
                archivarCatalogoServidor().catch(mostrarError);
            };
            if (window.Swal) {
                Swal.fire({
                    title: "Archivar catalogo",
                    text: "El catalogo dejara de aparecer en el selector, pero se conservara el historial.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Archivar",
                    cancelButtonText: "Cancelar"
                }).then((resultado) => {
                    if (resultado.isConfirmed) ejecutar();
                });
                return;
            }
            if (confirm("El catalogo se archivara sin borrado fisico. Continuar?")) ejecutar();
        });
        $("cc_reiniciar_borrador")?.addEventListener("click", () => {
            const ejecutar = () => {
                estado.catalogoActualId = 0;
                if ($("cc_borradores_guardados")) $("cc_borradores_guardados").value = "";
                if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = "";
                reiniciarBorradorLocal();
                setEstado("Nuevo catalogo", "success");
            };
            if (window.Swal) {
                Swal.fire({
                    title: "Nuevo catalogo",
                    text: "Se limpiara la seleccion actual para empezar otro catalogo. Los catalogos guardados no se borran.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Nuevo",
                    cancelButtonText: "Cancelar"
                }).then((resultado) => {
                    if (resultado.isConfirmed) ejecutar();
                });
                return;
            }
            if (confirm("Se limpiara la seleccion actual para empezar otro catalogo. Continuar?")) ejecutar();
        });
        $("cc_limpiar")?.addEventListener("click", () => {
            estado.seleccion.clear();
            guardarSeleccionLocal();
            renderTabla();
            renderSeleccion();
        });
        ["cc_mostrar_precio", "cc_mostrar_marca", "cc_mostrar_categoria", "cc_mostrar_presentacion", "cc_mostrar_sku", "cc_mostrar_disponibilidad", "cc_agrupar_variantes", "cc_plantilla", "cc_columnas_exportacion", "cc_filas_exportacion", "cc_fuente_visual", "cc_tam_titulo", "cc_tam_producto", "cc_tam_meta", "cc_tam_precio", "cc_portada_tipo"].forEach((id) => {
            $(id)?.addEventListener("change", () => {
                renderSeleccion();
                guardarOpcionesLocal();
                limpiarPreviewPaginasExportacion();
            });
        });
        ["cc_color_titulo", "cc_color_producto", "cc_color_meta", "cc_color_precio"].forEach((id) => {
            $(id)?.addEventListener("input", () => {
                renderSeleccion();
                guardarOpcionesLocal();
                limpiarPreviewPaginasExportacion();
            });
        });
        ["cc_material_titulo", "cc_material_subtitulo", "cc_material_cta", "cc_portada_etiqueta", "cc_portada_imagen_url", "cc_logo_url", "cc_contacto_texto", "cc_portada_descripcion", "cc_portada_nota"].forEach((id) => {
            $(id)?.addEventListener("input", () => {
                renderSeleccion();
                limpiarPreviewPaginasExportacion();
            });
        });
        $("cc_portada_activa")?.addEventListener("change", () => {
            renderSeleccion();
            limpiarPreviewPaginasExportacion();
        });
        $("cc_q")?.addEventListener("keydown", (event) => {
            if (event.key === "Enter") cargar().catch(mostrarError);
        });
        $("cc_categoria")?.addEventListener("change", () => {
            cargar().catch(mostrarError);
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
            const editarCatalogo = event.target.closest("[data-cc-editar-catalogo]");
            if (editarCatalogo) {
                cargarCatalogoServidor(editarCatalogo.getAttribute("data-cc-editar-catalogo")).then(() => mostrarVista("editor")).catch(mostrarError);
            }
            const archivarCatalogo = event.target.closest("[data-cc-archivar-catalogo]");
            if (archivarCatalogo) {
                const idCatalogo = archivarCatalogo.getAttribute("data-cc-archivar-catalogo");
                const ejecutar = () => archivarCatalogoServidor(idCatalogo).catch(mostrarError);
                if (window.Swal) {
                    Swal.fire({
                        title: "Archivar catalogo",
                        text: "El catalogo dejara de aparecer en Guardados, pero se conserva el historial.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Archivar",
                        cancelButtonText: "Cancelar"
                    }).then((resultado) => {
                        if (resultado.isConfirmed) ejecutar();
                    });
                    return;
                }
                if (confirm("El catalogo se archivara sin borrado fisico. Continuar?")) ejecutar();
            }
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

    async function iniciarCatalogosComerciales() {
        leerModoInicial();
        cargarSeleccionLocal();
        cargarMaterialLocal();
        cargarOpcionesLocal();
        enlazarEventos();
        renderSeleccion();
        if (estado.nuevoInicial) {
            estado.catalogoActualId = 0;
            estado.seleccion.clear();
            guardarSeleccionLocal();
            if ($("cc_borrador_nombre")) $("cc_borrador_nombre").value = "";
            renderSeleccion();
        }
        await cargarCategorias();
        await cargarCatalogosGuardados();
        if (estado.catalogoInicialId > 0) {
            await cargarCatalogoServidor(estado.catalogoInicialId);
        }
        mostrarVista(estado.modoInicial);
        await cargar();
    }

    document.addEventListener("DOMContentLoaded", () => {
        iniciarCatalogosComerciales().catch(mostrarError);
    });
})();












