"use strict";
(function () {
    var etiquetasActuales = [];
    var etiquetasSeleccionadas = {};

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function filtros() {
        return new URLSearchParams({
            q: document.getElementById("almacen_etiquetado_buscar").value.trim(),
            id_almacen: document.getElementById("almacen_etiquetado_almacen").value,
            estado_etiqueta: document.getElementById("almacen_etiquetado_estado").value
        }).toString();
    }
    function cargarCatalogos() {
        return request("/almacen/consultar_almacenes").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            document.getElementById("almacen_etiquetado_almacen").innerHTML = "<option value=\"\">Todos los almacenes</option>" + (response.depurar || []).map(function (item) {
                return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
            aplicarFiltrosUrl();
        });
    }
    function aplicarFiltrosUrl() {
        var params = new URLSearchParams(window.location.search);
        if (params.has("q")) {
            document.getElementById("almacen_etiquetado_buscar").value = params.get("q") || "";
        }
        if (params.has("estado_etiqueta")) {
            document.getElementById("almacen_etiquetado_estado").value = params.get("estado_etiqueta") || "";
        }
        if (params.has("id_almacen")) {
            document.getElementById("almacen_etiquetado_almacen").value = params.get("id_almacen") || "";
        }
    }
    function cargar() {
        request("/almacen/etiquetas_erp?" + filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            etiquetasSeleccionadas = {};
            render(response.depurar || []);
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: renderiza etiquetas de recepcion, preparacion e inventario con contenido y estado fisico.
     * Impacto: UI de Almacen/Etiquetado; evita presentar todas las etiquetas como si nacieran solo de recepcion.
     */
    function render(items) {
        etiquetasActuales = items;
        var pendientes = items.filter(function (item) { return item.estado_etiqueta === "pendiente_impresion"; }).length;
        var impresas = items.filter(function (item) { return item.estado_etiqueta === "impresa"; }).length;
        var pegadas = items.filter(function (item) { return item.estado_etiqueta === "pegada"; }).length;
        document.getElementById("almacen_etiquetado_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Unidades " + items.length + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Pendientes " + pendientes + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Impresas " + impresas + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Pegadas " + pegadas + "</span>" +
            "<span class=\"badge badge-light-dark fs-7\" id=\"almacen_etiquetado_seleccion_resumen\">Seleccionadas 0</span>";
        document.getElementById("almacen_etiquetado_body").innerHTML = items.map(function (item, index) {
            var codigo = item.codigo_etiqueta_interna || item.serie_fabricante || item.codigo_unico || "";
            var estado = estadoEtiqueta(item.estado_etiqueta);
            var fisico = estadoFisico(item.estado_fisico);
            var origen = origenEtiqueta(item);
            return "<tr><td><input class=\"form-check-input almacen-etiqueta-check\" type=\"checkbox\" data-etiqueta-seleccion=\"" + index + "\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(codigo) + "</div><span class=\"text-muted fs-8\">" + escapeHtml(item.tipo_identidad || "-") + "</span></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(origen.folio) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(origen.tipo) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + contenidoUnidad(item) + "</div><div class=\"text-muted fs-8\">Lote " + escapeHtml(item.lote || "-") + " | Cad. " + escapeHtml(item.fecha_caducidad || "-") + "</div></td>" +
                "<td><span class=\"badge " + estado.clase + " me-1\">" + estado.texto + "</span><span class=\"badge " + fisico.clase + "\">" + fisico.texto + "</span></td>" +
                "<td class=\"text-end\">" + accionesEtiqueta(item, index) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin etiquetas</td></tr>";
        document.getElementById("almacen_etiquetado_seleccionar_todo").checked = false;
        actualizarSeleccion();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: muestra contenido fisico disponible de una etiqueta/unidad sin asumir que siempre equivale a 1 pieza.
     * Impacto: UI de Almacen/Etiquetado; soporta unidades cerradas, abiertas y presentaciones preparadas.
     */
    function contenidoUnidad(item) {
        var cantidad = Number(item.cantidad_base_disponible || 0);
        var unidad = item.unidad_base || "";
        if (!cantidad && Number(item.cantidad_base_original || 0) > 0) {
            cantidad = Number(item.cantidad_base_original || 0);
        }
        return escapeHtml(cantidad.toLocaleString("es-MX", {minimumFractionDigits: 0, maximumFractionDigits: 6}) + (unidad ? " " + unidad : ""));
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: traduce el origen tecnico de una etiqueta a un origen operativo legible.
     * Impacto: UI de Almacen/Etiquetado; separa recepcion, preparacion, inventario inicial y regularizaciones.
     */
    function origenEtiqueta(item) {
        var tipo = item.origen_tipo || "";
        if (tipo === "preparacion_presentacion") {
            return {tipo: "Preparacion/Empaque", folio: item.folio_recepcion || "-"};
        }
        if (tipo === "recepcion_compra") {
            return {tipo: item.folio_orden_compra || "Recepcion", folio: item.folio_recepcion || "-"};
        }
        if (tipo === "inventario_inicial") {
            return {tipo: "Inventario inicial", folio: item.folio_recepcion || "-"};
        }
        if (tipo === "regularizacion_trazabilidad_uat") {
            return {tipo: "Origen " + (item.origen_id || "-"), folio: "Regularizacion UAT"};
        }
        return {tipo: item.origen_id ? "Origen " + item.origen_id : "-", folio: item.folio_recepcion || tipo || "-"};
    }
    function estadoEtiqueta(estado) {
        if (estado === "pegada") { return {clase: "badge-light-success", texto: "Pegada"}; }
        if (estado === "impresa") { return {clase: "badge-light-info", texto: "Impresa"}; }
        if (estado === "reimpresa") { return {clase: "badge-light-primary", texto: "Reimpresa"}; }
        if (estado === "cancelada") { return {clase: "badge-light-danger", texto: "Cancelada"}; }
        return {clase: "badge-light-warning", texto: "Pendiente impresion"};
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: presenta el estado fisico de una unidad etiquetada junto al estado de impresion/pegado.
     * Impacto: UI de Almacen/Etiquetado; permite distinguir unidad cerrada, abierta o consumida.
     */
    function estadoFisico(estado) {
        if (estado === "abierta") { return {clase: "badge-light-warning", texto: "Abierta"}; }
        if (estado === "consumida") { return {clase: "badge-light-dark", texto: "Consumida"}; }
        if (estado === "agotada") { return {clase: "badge-light-dark", texto: "Agotada"}; }
        return {clase: "badge-light-success", texto: "Cerrada"};
    }
    function accionesEtiqueta(item, index) {
        var imprimir = "<button class=\"btn btn-sm btn-light-primary me-2\" type=\"button\" data-etiqueta-imprimir=\"" + index + "\"><i class=\"bi bi-printer\"></i> Imprimir</button>";
        if (item.estado_etiqueta === "pendiente_impresion") {
            return imprimir;
        }
        if (item.estado_etiqueta === "impresa" || item.estado_etiqueta === "reimpresa") {
            return imprimir + "<button class=\"btn btn-sm btn-light-success\" type=\"button\" data-etiqueta-pegada=\"" + escapeHtml(item.id_inventario_unidad) + "\"><i class=\"bi bi-check2-circle\"></i> Marcar pegada</button>";
        }
        if (item.estado_etiqueta === "pegada") {
            return imprimir;
        }
        return "<span class=\"text-muted fs-8\">Sin acciones</span>";
    }
    function cambiarEstadoEtiqueta(url, id, textoConfirmacion) {
        Swal.fire({
            text: textoConfirmacion,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Confirmar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            post(url, {id_inventario_unidad: id}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                cargar();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }
    function imprimirEtiqueta(item) {
        imprimirEtiquetas([item], true);
    }
    function imprimirEtiquetas(items, preguntarMarcado) {
        items = (items || []).filter(Boolean);
        if (!items.length) {
            Swal.fire({text: "Selecciona al menos una etiqueta.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var html = plantillaImpresionEtiquetas(items);
        var ventana = window.open("", "_blank", "width=820,height=620");
        if (!ventana) {
            Swal.fire({text: "El navegador bloqueo la ventana de impresion.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
        ventana.focus();
        setTimeout(function () {
            ventana.print();
        }, 150);

        var pendientes = items.filter(function (item) { return item.estado_etiqueta === "pendiente_impresion"; });
        if (preguntarMarcado && pendientes.length) {
            Swal.fire({
                text: "Si la impresion fue correcta, se marcaran " + pendientes.length + " etiqueta(s) como impresa(s).",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Marcar impresas",
                cancelButtonText: "Solo imprimir"
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                marcarImpresas(pendientes);
            });
        }
    }
    function marcarImpresas(items) {
        var ids = items.map(function (item) { return item.id_inventario_unidad; });
        post("/almacen/etiquetas_marcar_impresas_erp", {ids: JSON.stringify(ids)}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    function marcarPegadas(items) {
        items = (items || []).filter(Boolean);
        if (!items.length) {
            Swal.fire({text: "Selecciona al menos una etiqueta.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            text: "Confirma que " + items.length + " etiqueta(s) ya fueron pegadas fisicamente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Marcar pegadas",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            var ids = items.map(function (item) { return item.id_inventario_unidad; });
            post("/almacen/etiquetas_marcar_pegadas_erp", {ids: JSON.stringify(ids)}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                cargar();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }
    function datosEtiqueta(item) {
        var codigo = item.codigo_etiqueta_interna || item.serie_fabricante || item.codigo_unico || "";
        return {
            codigo: codigo,
            sku: item.sku || "",
            producto: item.producto || "",
            barcode: code128Svg(codigo)
        };
    }
    function etiquetaHtml(datos) {
        return "<div class=\"label\"><div class=\"product\">" + escapeHtml(datos.producto || "") + "</div>" +
            "<div class=\"sku\">" + escapeHtml(datos.sku || "-") + "</div><div class=\"barcode\">" + datos.barcode + "</div>" +
            "<div class=\"code\">" + escapeHtml(datos.codigo) + "</div></div>";
    }
    function plantillaImpresionEtiquetas(items) {
        var titulo = items.length === 1 ? "Etiqueta " + (datosEtiqueta(items[0]).codigo || "") : "Etiquetas de trazabilidad";
        var etiquetas = items.map(function (item) { return etiquetaHtml(datosEtiqueta(item)); }).join("");
        return "<!doctype html><html><head><meta charset=\"utf-8\"><title>" + escapeHtml(titulo) + "</title>" +
            "<style>@page{size:50mm 30mm;margin:2mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:0;color:#111}.sheet{display:flex;flex-wrap:wrap;gap:2mm;align-items:flex-start}.label{width:46mm;height:26mm;padding:2mm;border:1px solid #111;display:flex;flex-direction:column;justify-content:space-between;break-inside:avoid;page-break-inside:avoid}.product{font-weight:700;font-size:8px;line-height:1.15;max-height:9mm;overflow:hidden}.sku{font-weight:700;font-size:7px;text-align:center;color:#333}.code{text-align:center;font-weight:700;font-size:9px;letter-spacing:0}.barcode svg{width:100%;height:11mm;display:block}@media print{body{margin:0}.sheet{gap:0}.label{margin:0}}</style>" +
            "</head><body><div class=\"sheet\">" + etiquetas + "</div></body></html>";
    }
    function code128Svg(value) {
        var patterns = [
            "11011001100", "11001101100", "11001100110", "10010011000", "10010001100", "10001001100", "10011001000", "10011000100", "10001100100", "11001001000",
            "11001000100", "11000100100", "10110011100", "10011011100", "10011001110", "10111001100", "10011101100", "10011100110", "11001110010", "11001011100",
            "11001001110", "11011100100", "11001110100", "11101101110", "11101001100", "11100101100", "11100100110", "11101100100", "11100110100", "11100110010",
            "11011011000", "11011000110", "11000110110", "10100011000", "10001011000", "10001000110", "10110001000", "10001101000", "10001100010", "11010001000",
            "11000101000", "11000100010", "10110111000", "10110001110", "10001101110", "10111011000", "10111000110", "10001110110", "11101110110", "11010001110",
            "11000101110", "11011101000", "11011100010", "11011101110", "11101011000", "11101000110", "11100010110", "11101101000", "11101100010", "11100011010",
            "11101111010", "11001000010", "11110001010", "10100110000", "10100001100", "10010110000", "10010000110", "10000101100", "10000100110", "10110010000",
            "10110000100", "10011010000", "10011000010", "10000110100", "10000110010", "11000010010", "11001010000", "11110111010", "11000010100", "10001111010",
            "10100111100", "10010111100", "10010011110", "10111100100", "10011110100", "10011110010", "11110100100", "11110010100", "11110010010", "11011011110",
            "11011110110", "11110110110", "10101111000", "10100011110", "10001011110", "10111101000", "10111100010", "11110101000", "11110100010", "10111011110",
            "10111101110", "11101011110", "11110101110", "11010000100", "11010010000", "11010011100", "1100011101011"
        ];
        var chars = String(value || "").split("");
        var codes = [104];
        var checksum = 104;
        chars.forEach(function (char, index) {
            var ascii = char.charCodeAt(0);
            var code = ascii >= 32 && ascii <= 126 ? ascii - 32 : 0;
            codes.push(code);
            checksum += code * (index + 1);
        });
        codes.push(checksum % 103);
        codes.push(106);
        var bits = codes.map(function (code) { return patterns[code] || patterns[0]; }).join("");
        var width = bits.length;
        var rects = "";
        var x = 0;
        while (x < bits.length) {
            if (bits.charAt(x) === "1") {
                var start = x;
                while (x < bits.length && bits.charAt(x) === "1") { x++; }
                rects += "<rect x=\"" + start + "\" y=\"0\" width=\"" + (x - start) + "\" height=\"40\"/>";
                continue;
            }
            x++;
        }
        return "<svg viewBox=\"0 0 " + width + " 40\" preserveAspectRatio=\"none\" xmlns=\"http://www.w3.org/2000/svg\">" + rects + "</svg>";
    }
    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos().then(cargar).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
        document.getElementById("almacen_etiquetado_recargar").addEventListener("click", cargar);
        document.getElementById("almacen_etiquetado_almacen").addEventListener("change", cargar);
        document.getElementById("almacen_etiquetado_estado").addEventListener("change", cargar);
        document.getElementById("almacen_etiquetado_imprimir_seleccion").addEventListener("click", function () {
            imprimirEtiquetas(etiquetasSeleccionadasLista(), true);
        });
        document.getElementById("almacen_etiquetado_pegar_seleccion").addEventListener("click", function () {
            marcarPegadas(etiquetasSeleccionadasLista());
        });
        document.getElementById("almacen_etiquetado_seleccionar_todo").addEventListener("change", function (event) {
            etiquetasActuales.forEach(function (item, index) {
                etiquetasSeleccionadas[index] = event.target.checked;
            });
            document.querySelectorAll(".almacen-etiqueta-check").forEach(function (input) {
                input.checked = event.target.checked;
            });
            actualizarSeleccion();
        });
        document.getElementById("almacen_etiquetado_body").addEventListener("click", function (event) {
            var imprimir = event.target.closest("[data-etiqueta-imprimir]");
            var seleccion = event.target.closest("[data-etiqueta-seleccion]");
            var impresa = event.target.closest("[data-etiqueta-impresa]");
            var pegada = event.target.closest("[data-etiqueta-pegada]");
            if (seleccion) {
                etiquetasSeleccionadas[Number(seleccion.getAttribute("data-etiqueta-seleccion"))] = seleccion.checked;
                actualizarSeleccion();
            }
            if (imprimir) {
                imprimirEtiqueta(etiquetasActuales[Number(imprimir.getAttribute("data-etiqueta-imprimir"))]);
            }
            if (impresa) {
                cambiarEstadoEtiqueta("/almacen/etiqueta_marcar_impresa_erp", impresa.getAttribute("data-etiqueta-impresa"), "Confirma que la etiqueta ya fue impresa fisicamente.");
            }
            if (pegada) {
                cambiarEstadoEtiqueta("/almacen/etiqueta_marcar_pegada_erp", pegada.getAttribute("data-etiqueta-pegada"), "Confirma que la etiqueta ya fue pegada en la unidad.");
            }
        });
        var timer;
        document.getElementById("almacen_etiquetado_buscar").addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(cargar, 250);
        });
    });
    function etiquetasSeleccionadasLista() {
        return Object.keys(etiquetasSeleccionadas).filter(function (index) {
            return etiquetasSeleccionadas[index];
        }).map(function (index) {
            return etiquetasActuales[Number(index)];
        }).filter(Boolean);
    }
    function actualizarSeleccion() {
        var total = etiquetasSeleccionadasLista().length;
        var resumen = document.getElementById("almacen_etiquetado_seleccion_resumen");
        var botonImprimir = document.getElementById("almacen_etiquetado_imprimir_seleccion");
        var botonPegar = document.getElementById("almacen_etiquetado_pegar_seleccion");
        if (resumen) { resumen.textContent = "Seleccionadas " + total; }
        if (botonImprimir) { botonImprimir.disabled = total <= 0; }
        if (botonPegar) { botonPegar.disabled = total <= 0; }
    }
})();
