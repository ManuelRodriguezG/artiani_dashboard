(function () {
    "use strict";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-26
     * Proposito: operar el listado tabular de catalogos comerciales.
     * Impacto: Comercial/Catalogo ERP; separa consulta y acciones de listado del constructor de catalogos.
     * Contrato: no edita productos ni genera exportaciones; archivar usa endpoint protegido existente.
     */
    const estado = { catalogos: [], filtro: "" };
    const $ = (id) => document.getElementById(id);

    function escapeHtml(valor) {
        return String(valor ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
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

    function setEstado(texto, tipo) {
        const el = $("cc_listado_estado");
        if (!el) return;
        el.className = `badge badge-light-${tipo || "primary"}`;
        el.textContent = texto;
    }

    function fechaTexto(valor) {
        if (!valor) return "Sin fecha";
        const fecha = new Date(String(valor).replace(" ", "T"));
        if (Number.isNaN(fecha.getTime())) return String(valor);
        return fecha.toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
    }

    function catalogosFiltrados() {
        const q = estado.filtro.trim().toLowerCase();
        if (!q) return estado.catalogos;
        return estado.catalogos.filter((catalogo) => {
            return [catalogo.codigo, catalogo.nombre, catalogo.titulo, catalogo.subtitulo, catalogo.plantilla, catalogo.estatus]
                .some((valor) => String(valor || "").toLowerCase().includes(q));
        });
    }

    function renderResumen() {
        const totalItems = estado.catalogos.reduce((total, catalogo) => total + Number(catalogo.total_items || 0), 0);
        const borradores = estado.catalogos.filter((catalogo) => String(catalogo.estatus || "") === "borrador").length;
        if ($("cc_listado_total")) $("cc_listado_total").textContent = estado.catalogos.length.toLocaleString("es-MX");
        if ($("cc_listado_items")) $("cc_listado_items").textContent = totalItems.toLocaleString("es-MX");
        if ($("cc_listado_borradores")) $("cc_listado_borradores").textContent = borradores.toLocaleString("es-MX");
    }

    function renderTabla() {
        const body = $("cc_listado_body");
        if (!body) return;
        const catalogos = catalogosFiltrados();
        if (!catalogos.length) {
            body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-8">Sin catalogos comerciales guardados</td></tr>`;
            return;
        }
        body.innerHTML = catalogos.map((catalogo) => {
            const id = encodeURIComponent(catalogo.id_catalogo_comercial);
            return `<tr>
                <td>
                    <div class="fw-bold text-gray-900">${escapeHtml(catalogo.nombre || "Catalogo sin nombre")}</div>
                    <div class="text-muted fs-8">${escapeHtml(catalogo.codigo || "")}</div>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(catalogo.titulo || "")}</div>
                    <div class="text-muted fs-8">${escapeHtml(catalogo.subtitulo || "")}</div>
                </td>
                <td><span class="badge badge-light-primary">${escapeHtml(catalogo.plantilla || "square")}</span></td>
                <td class="text-end fw-bold">${Number(catalogo.total_items || 0).toLocaleString("es-MX")}</td>
                <td><span class="badge badge-light-${String(catalogo.estatus || "") === "borrador" ? "warning" : "success"}">${escapeHtml(catalogo.estatus || "")}</span></td>
                <td>${escapeHtml(fechaTexto(catalogo.fecha_actualizacion || catalogo.fecha_registro))}</td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        <a class="btn btn-sm btn-light-dark" href="/catalogoerp/catalogos_comerciales_ver?id_catalogo_comercial=${id}"><i class="bi bi-eye"></i> Ver</a>
                        <a class="btn btn-sm btn-light-primary" href="/catalogoerp/catalogos_comerciales_editar?id_catalogo_comercial=${id}"><i class="bi bi-pencil"></i> Editar</a>
                        <button class="btn btn-sm btn-light-danger" type="button" data-cc-archivar="${escapeHtml(catalogo.id_catalogo_comercial)}"><i class="bi bi-archive"></i> Archivar</button>
                    </div>
                </td>
            </tr>`;
        }).join("");
    }

    async function cargar() {
        setEstado("Cargando", "warning");
        const json = await apiGet("/catalogoerp/catalogos_comerciales_listar");
        if (json.error) throw new Error(json.mensaje || "No se pudieron consultar catalogos");
        estado.catalogos = json.depurar && Array.isArray(json.depurar.catalogos) ? json.depurar.catalogos : [];
        renderResumen();
        renderTabla();
        setEstado("Listo", "success");
    }

    async function archivar(idCatalogo) {
        const json = await apiPost("/catalogoerp/catalogos_comerciales_archivar", { id_catalogo_comercial: idCatalogo });
        if (json.error) throw new Error(json.mensaje || "No se pudo archivar el catalogo");
        await cargar();
        setEstado("Archivado", "success");
    }

    function confirmarArchivar(idCatalogo) {
        const ejecutar = () => archivar(idCatalogo).catch(mostrarError);
        if (window.Swal) {
            Swal.fire({
                title: "Archivar catalogo",
                text: "El catalogo dejara de aparecer en el listado, pero conservara historial.",
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

    function mostrarError(error) {
        setEstado("Error", "danger");
        if (window.Swal) {
            Swal.fire("Catalogos comerciales", error.message || "No se pudo completar la accion", "error");
            return;
        }
        alert(error.message || "No se pudo completar la accion");
    }

    document.addEventListener("DOMContentLoaded", () => {
        $("cc_listado_recargar")?.addEventListener("click", () => cargar().catch(mostrarError));
        $("cc_listado_buscar")?.addEventListener("input", (event) => {
            estado.filtro = event.target.value || "";
            renderTabla();
        });
        document.addEventListener("click", (event) => {
            const boton = event.target.closest("[data-cc-archivar]");
            if (boton) confirmarArchivar(boton.getAttribute("data-cc-archivar"));
        });
        cargar().catch(mostrarError);
    });
})();
