"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-20
     * Proposito: operar la portada/listado de Listas de precios.
     * Impacto: separa consulta de creacion/edicion para reducir errores operativos.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function alerta(tipo, mensaje) {
        document.getElementById("lp_inicio_alerta").innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-4\">" + escapeHtml(mensaje || "") + "</div>";
    }

    function filtros() {
        return {
            q: document.getElementById("lp_inicio_q").value.trim(),
            estatus: document.getElementById("lp_inicio_estatus").value,
            canal: document.getElementById("lp_inicio_canal").value,
            limite: "120"
        };
    }

    function cargar() {
        document.getElementById("lp_inicio_listas").innerHTML = "<div class=\"text-muted py-8 text-center\">Cargando listas...</div>";
        request("/comercial/listas_precios_resumen_erp?" + new URLSearchParams(filtros()).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            renderKpis(data.kpis || {});
            renderListas(data.listas || []);
            if ((data.conflictos || []).length) {
                alerta("warning", "Hay observaciones comerciales en listas. Abre la lista para revisar.");
            } else {
                document.getElementById("lp_inicio_alerta").innerHTML = "";
            }
        }).catch(function (error) {
            alerta("danger", error.message || String(error));
        });
        cargarFase1();
    }

    function cargarFase1() {
        request("/comercial/listas_precios_fase1_readiness_erp").then(function (response) {
            var data = response.depurar || {};
            var listo = !!data.puede_piloto_pos;
            document.getElementById("lp_inicio_fase1").textContent = listo ? "Listo piloto POS" : "Con bloqueos";
            document.getElementById("lp_inicio_fase1").className = listo ? "fw-bold text-success" : "fw-bold text-warning";
            document.getElementById("lp_inicio_fase1_detalle").textContent = listo ? "Ecommerce y granel siguen fase posterior" : ((data.bloqueos || [])[0] || "Revisar semaforo");
        }).catch(function () {
            document.getElementById("lp_inicio_fase1").textContent = "Sin validar";
            document.getElementById("lp_inicio_fase1_detalle").textContent = "No se pudo cargar semaforo";
        });
    }

    function renderKpis(kpis) {
        document.getElementById("lp_kpi_listas").textContent = Number(kpis.listas_total || 0);
        document.getElementById("lp_kpi_activas").textContent = Number(kpis.listas_activas || 0);
        document.getElementById("lp_kpi_detalles").textContent = Number(kpis.detalles_activos || 0);
        document.getElementById("lp_kpi_segmentos").textContent = Number(kpis.segmentos_asignados || 0);
    }

    function renderListas(listas) {
        if (!listas.length) {
            document.getElementById("lp_inicio_listas").innerHTML = "<div class=\"text-center text-muted py-10\">Sin listas para los filtros actuales.</div>";
            return;
        }
        document.getElementById("lp_inicio_listas").innerHTML = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\">" +
            "<thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Lista</th><th>Alcance</th><th>Precios</th><th>Asignaciones</th><th>Vigencia</th><th class=\"text-end\">Acciones</th></tr></thead><tbody>" +
            listas.map(function (item) {
                var canal = item.canal || "general";
                var almacen = item.id_almacen && Number(item.id_almacen) > 0 ? "Almacen " + item.id_almacen : "Todos";
                var rango = item.precio_min == null ? "Sin precios" : dinero(item.precio_min) + (Number(item.precio_max || 0) !== Number(item.precio_min || 0) ? " - " + dinero(item.precio_max) : "");
                var editar = "/comercial/listas_precios_editar?id_lista_precio=" + encodeURIComponent(item.id_lista_precio || "");
                return "<tr>" +
                    "<td><div class=\"fw-bold\">" + escapeHtml(item.codigo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div>" + badge(item.estatus || "") + "</td>" +
                    "<td><div class=\"fw-semibold\">" + escapeHtml(canal) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(almacen) + " | prioridad " + escapeHtml(item.prioridad || "100") + "</div></td>" +
                    "<td><div class=\"fw-semibold\">" + escapeHtml(item.detalles_activos || 0) + " activo(s)</div><div class=\"text-muted fs-8\">" + escapeHtml(rango) + "</div></td>" +
                    "<td><div class=\"fw-semibold\">" + escapeHtml(item.asignaciones_activas || 0) + " cliente(s)</div><div class=\"text-muted fs-8\">Segmentos en editor</div></td>" +
                    "<td><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_inicio || "Sin inicio") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_fin || "Sin fin") + "</div></td>" +
                    "<td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"" + editar + "\"><i class=\"bi bi-pencil-square\"></i> Editar</a></td>" +
                "</tr>";
            }).join("") + "</tbody></table></div>";
    }

    function badge(estatus) {
        var clases = {activa: "badge-light-success", borrador: "badge-light", pausada: "badge-light-warning", cancelada: "badge-light-danger"};
        return "<span class=\"badge " + (clases[estatus] || "badge-light") + " mt-2\">" + escapeHtml(estatus || "-") + "</span>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("lp_inicio_recargar").addEventListener("click", cargar);
        document.getElementById("lp_inicio_filtrar").addEventListener("click", cargar);
        document.getElementById("lp_inicio_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                cargar();
            }
        });
        document.getElementById("lp_inicio_estatus").addEventListener("change", cargar);
        document.getElementById("lp_inicio_canal").addEventListener("change", cargar);
        cargar();
    });
})();
