"use strict";
(function () {
    var analisisActual = null;

    function esc(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : value;
        return div.innerHTML;
    }

    function badge(texto, color) {
        return "<span class=\"badge badge-light-" + esc(color || "primary") + " me-2 mb-2\">" + esc(texto) + "</span>";
    }

    function postForm(url, formData) {
        return fetch(url, {
            method: "POST",
            headers: {"X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: formData,
            credentials: "same-origin"
        }).then(function (response) {
            return response.text();
        }).then(function (text) {
            if (!text) {
                throw new Error("El servidor no devolvio respuesta.");
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error("Respuesta no valida: " + text.substring(0, 180));
            }
        });
    }

    function setError(message) {
        var error = document.getElementById("proveedores_vivos_error");
        error.textContent = message || "";
        error.classList.toggle("d-none", !message);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-21
     * Proposito: ejecutar preview read-only de listas variables de peces vivos.
     * Impacto: UI Proveedores; no persiste datos ni dispara flujos de Compras/Catalogo.
     */
    function analizar(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var boton = document.getElementById("proveedores_vivos_analizar");
        var estado = document.getElementById("proveedores_vivos_estado");
        var data = new FormData(form);
        data.set("_csrf", window.ERP_CSRF_TOKEN || data.get("_csrf") || "");
        setError("");
        boton.disabled = true;
        estado.textContent = "Analizando";
        estado.className = "badge badge-light-warning";

        postForm("/proveedor/proveedor_lista_variable_vivos_preview_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible analizar la lista.");
            }
            analisisActual = response.depurar || {};
            estado.textContent = "Analizada";
            estado.className = "badge badge-light-success";
            renderAnalisis();
        }).catch(function (err) {
            analisisActual = null;
            estado.textContent = "Error";
            estado.className = "badge badge-light-danger";
            setError(err.message);
        }).finally(function () {
            boton.disabled = false;
        });
    }

    function renderAnalisis() {
        var data = analisisActual || {};
        var archivo = data.archivo || {};
        var resumen = data.resumen || {};
        document.getElementById("proveedores_vivos_archivo_nombre").textContent = [
            archivo.nombre || "Archivo",
            archivo.hoja ? "Hoja: " + archivo.hoja : "",
            data.formato_detectado || ""
        ].filter(Boolean).join(" | ");

        document.getElementById("proveedores_vivos_resumen").innerHTML = [
            resumenCard("Formato", etiquetaFormato(data.formato_detectado), "primary"),
            resumenCard("Productos", resumen.productos || 0, "success"),
            resumenCard("Secciones", resumen.secciones || 0, "info"),
            resumenCard("Notas", resumen.notas || 0, "warning"),
            resumenCard("Con minimo bolsa", resumen.con_minimo_bolsa || 0, "dark"),
            resumenCard("Sin escrituras", data.sin_escrituras ? "Si" : "No", "secondary")
        ].join("");

        renderCondiciones(data.condiciones || []);
        renderRenglones();
    }

    function etiquetaFormato(formato) {
        if (formato === "vivos_formal") {
            return "Formal";
        }
        if (formato === "vivos_compacto") {
            return "Compacto";
        }
        return formato || "-";
    }

    function resumenCard(titulo, valor, color) {
        return "<div class=\"col-sm-6 col-xl-2\"><div class=\"border rounded p-4 h-100\">" +
            "<div class=\"text-muted fs-8 text-uppercase\">" + esc(titulo) + "</div>" +
            "<div class=\"fs-2 fw-bold text-" + esc(color) + "\">" + esc(valor) + "</div>" +
            "</div></div>";
    }

    function renderCondiciones(condiciones) {
        var contenedor = document.getElementById("proveedores_vivos_condiciones");
        if (!condiciones.length) {
            contenedor.innerHTML = "<span class=\"text-muted\">Sin condiciones detectadas.</span>";
            return;
        }
        contenedor.innerHTML = condiciones.slice(0, 24).map(function (item) {
            var color = item.tipo === "descuento" ? "success" : (item.tipo === "encabezado" ? "primary" : "warning");
            return "<div class=\"border rounded p-3 flex-grow-1\" style=\"min-width:260px;max-width:520px;\">" +
                badge((item.tipo || "nota") + " | fila " + (item.fila || "-"), color) +
                "<div class=\"fs-7\">" + esc(item.texto || "") + "</div>" +
                "</div>";
        }).join("");
    }

    function renderRenglones() {
        var data = analisisActual || {};
        var renglones = data.renglones || [];
        var tipo = document.getElementById("proveedores_vivos_tipo").value || "todos";
        var busqueda = (document.getElementById("proveedores_vivos_buscar").value || "").toLowerCase();
        var filtrados = renglones.filter(function (renglon) {
            if (tipo !== "todos" && renglon.tipo_renglon !== tipo) {
                return false;
            }
            if (!busqueda) {
                return true;
            }
            return [
                renglon.texto,
                renglon.nombre_proveedor_raw,
                renglon.nombre_normalizado,
                renglon.seccion_lista,
                renglon.tamano_raw,
                renglon.clasificacion_proveedor_raw
            ].join(" ").toLowerCase().indexOf(busqueda) !== -1;
        });
        document.getElementById("proveedores_vivos_total").textContent = filtrados.length + " renglones";
        document.getElementById("proveedores_vivos_body").innerHTML = filtrados.map(rowHtml).join("") ||
            "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Sin renglones para el filtro actual.</td></tr>";
    }

    function rowHtml(renglon) {
        var tipo = renglon.tipo_renglon || "nota";
        var color = {
            producto: "success",
            seccion: "info",
            nota: "warning",
            descuento: "primary",
            basura: "secondary"
        }[tipo] || "secondary";
        var datosPedido = [
            renglon.clasificacion_proveedor_raw ? "Marca: " + renglon.clasificacion_proveedor_raw : "",
            renglon.cantidad_pedida !== "" ? "Pedida: " + renglon.cantidad_pedida : "",
            renglon.cantidad_por_bolsa !== "" ? "Bolsa: " + renglon.cantidad_por_bolsa : "",
            renglon.tipo_bolsa ? "Tipo: " + renglon.tipo_bolsa : "",
            renglon.numero_caja ? "Caja: " + renglon.numero_caja : ""
        ].filter(Boolean).join(" | ");
        return "<tr>" +
            "<td class=\"text-muted\">" + esc(renglon.fila_origen || "") + "</td>" +
            "<td>" + badge(tipo, color) + "</td>" +
            "<td>" + esc(renglon.seccion_lista || "-") + "</td>" +
            "<td><div class=\"fw-semibold\">" + esc(renglon.nombre_proveedor_raw || renglon.texto || "-") + "</div>" +
            "<span class=\"text-muted fs-8\">" + esc(renglon.nombre_normalizado || "") + "</span></td>" +
            "<td>" + esc(renglon.tamano_raw || "-") + "</td>" +
            "<td class=\"text-end fw-bold\">" + esc(renglon.precio_unitario || "-") + "</td>" +
            "<td class=\"text-end\">" + esc(renglon.minimo_por_bolsa || "-") + "</td>" +
            "<td class=\"fs-8 text-muted\">" + esc(datosPedido || "-") + "</td>" +
            "</tr>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("proveedores_vivos_form").addEventListener("submit", analizar);
        document.getElementById("proveedores_vivos_buscar").addEventListener("input", renderRenglones);
        document.getElementById("proveedores_vivos_tipo").addEventListener("change", renderRenglones);
    });
})();
