"use strict";
(function () {
    var piezas = [];
    var borradores = [];
    var storageKey = "erp_produccion_peceras_borradores_v1";

    function el(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function numero(value) {
        var parsed = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(parsed) ? parsed : 0;
    }
    function dinero(value) {
        return "$" + numero(value).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function cantidadEntera(value) {
        return Math.max(1, Math.round(numero(value) || 1));
    }
    function formato(value, decimales) {
        var n = numero(value);
        return Number(n.toFixed(decimales == null ? 2 : decimales)).toString();
    }
    function uuid() {
        return "pecera_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2, 8);
    }
    function toast(mensaje, tipo) {
        if (window.Swal) {
            Swal.fire({text: mensaje, icon: tipo || "success", buttonsStyling: false, confirmButtonText: "Entendido", customClass: {confirmButton: "btn btn-primary"}});
            return;
        }
        alert(mensaje);
    }
    function mostrarError(mensaje) {
        var caja = el("peceras_error");
        caja.textContent = mensaje || "";
        caja.classList.toggle("d-none", !mensaje);
    }
    function datosFormulario() {
        return {
            nombre: el("peceras_nombre").value.trim(),
            proveedor: el("peceras_proveedor").value.trim(),
            largo: numero(el("peceras_largo").value),
            fondo: numero(el("peceras_fondo").value),
            alto: numero(el("peceras_alto").value),
            espesor: numero(el("peceras_espesor").value),
            cantidad: cantidadEntera(el("peceras_cantidad").value),
            base: el("peceras_base").value,
            holgura: numero(el("peceras_holgura").value),
            descuento_corte: numero(el("peceras_descuento_corte").value),
            refuerzos: el("peceras_refuerzos").value,
            refuerzo_ancho: numero(el("peceras_refuerzo_ancho").value),
            tapa: el("peceras_tapa").value,
            tapa_piezas: cantidadEntera(el("peceras_tapa_piezas").value),
            costo_m2: numero(el("peceras_costo_m2").value),
            costo_corte: numero(el("peceras_costo_corte").value),
            observaciones: el("peceras_observaciones").value.trim()
        };
    }
    function medidasValidas(datos) {
        if (datos.largo <= 0 || datos.fondo <= 0 || datos.alto <= 0) {
            mostrarError("Captura largo, fondo y alto mayores a cero.");
            return false;
        }
        if (datos.espesor <= 0) {
            mostrarError("Selecciona un espesor valido.");
            return false;
        }
        mostrarError("");
        return true;
    }
    function crearPieza(nombre, largo, ancho, cantidad, espesor, acabado, observaciones, generada) {
        return {
            id: uuid(),
            nombre: nombre,
            largo: Math.max(0, numero(largo)),
            ancho: Math.max(0, numero(ancho)),
            espesor: numero(espesor),
            cantidad: cantidadEntera(cantidad),
            acabado: acabado || "sin_pulir",
            observaciones: observaciones || "",
            generada: generada !== false
        };
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: generar cortes base para peceras de vidrio con reglas editables por operacion.
     * Impacto: UI Produccion/Peceras; no escribe en BD ni afecta inventario.
     * Contrato: calcula medidas sugeridas en cm; el usuario puede corregir cada pieza antes de pedir.
     */
    function generarPiezas() {
        var datos = datosFormulario();
        if (!medidasValidas(datos)) {
            return;
        }
        var espesorCm = datos.espesor / 10;
        var holguraCm = datos.holgura / 10;
        var lateralFondo = Math.max(0, datos.fondo - (2 * espesorCm) - holguraCm);
        var baseLargo = datos.base === "interior" ? Math.max(0, datos.largo - (2 * espesorCm) - holguraCm) : datos.largo;
        var baseFondo = datos.base === "interior" ? Math.max(0, datos.fondo - (2 * espesorCm) - holguraCm) : datos.fondo;

        piezas = [
            crearPieza("Frente", medidaPedido(datos.largo, datos), medidaPedido(datos.alto, datos), 1, datos.espesor, "filo_muerto", "", true),
            crearPieza("Fondo", medidaPedido(datos.largo, datos), medidaPedido(datos.alto, datos), 1, datos.espesor, "filo_muerto", "", true),
            crearPieza("Lateral izquierdo", medidaPedido(lateralFondo, datos), medidaPedido(datos.alto, datos), 1, datos.espesor, "filo_muerto", "", true),
            crearPieza("Lateral derecho", medidaPedido(lateralFondo, datos), medidaPedido(datos.alto, datos), 1, datos.espesor, "filo_muerto", "", true),
            crearPieza("Base", medidaPedido(baseLargo, datos), medidaPedido(baseFondo, datos), 1, datos.espesor, "filo_muerto", datos.base === "interior" ? "Base calculada interior" : "Base calculada exterior", true)
        ];
        agregarPiezasOpcionales(datos, lateralFondo);
        renderPiezas();
        calcular();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: descontar tolerancia de corte configurada por el negocio a cada medida solicitada.
     * Impacto: Produccion/Peceras; corrige el pedido para maquinas con variacion aproximada de +/- 2 mm.
     * Contrato: recibe cm, resta descuento en mm convertido a cm y nunca devuelve negativo.
     */
    function medidaPedido(valorCm, datos) {
        return Math.max(0, numero(valorCm) - (Math.max(0, numero(datos.descuento_corte)) / 10));
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: agregar piezas comunes de armado sin obligar al usuario a capturarlas manualmente.
     * Impacto: UI Produccion/Peceras; refuerzos y tapa se integran al pedido tecnico.
     * Contrato: las medidas son sugeridas y editables antes de enviar al proveedor.
     */
    function agregarPiezasOpcionales(datos, lateralFondo) {
        var anchoRefuerzo = Math.max(0, datos.refuerzo_ancho || 5);
        if (datos.refuerzos === "frente_fondo" || datos.refuerzos === "perimetral") {
            piezas.push(crearPieza("Refuerzo superior frente", medidaPedido(datos.largo, datos), medidaPedido(anchoRefuerzo, datos), 1, datos.espesor, "filo_muerto", "Refuerzo superior", true));
            piezas.push(crearPieza("Refuerzo superior fondo", medidaPedido(datos.largo, datos), medidaPedido(anchoRefuerzo, datos), 1, datos.espesor, "filo_muerto", "Refuerzo superior", true));
        }
        if (datos.refuerzos === "perimetral") {
            piezas.push(crearPieza("Refuerzo superior lateral izquierdo", medidaPedido(lateralFondo, datos), medidaPedido(anchoRefuerzo, datos), 1, datos.espesor, "filo_muerto", "Refuerzo superior", true));
            piezas.push(crearPieza("Refuerzo superior lateral derecho", medidaPedido(lateralFondo, datos), medidaPedido(anchoRefuerzo, datos), 1, datos.espesor, "filo_muerto", "Refuerzo superior", true));
        }
        if (datos.tapa === "si") {
            var piezasTapa = cantidadEntera(datos.tapa_piezas);
            var holguraTapaCm = Math.max(0.2, numero(datos.holgura) / 10);
            var largoTapa = Math.max(0, (datos.largo - (holguraTapaCm * 2)) / piezasTapa);
            var fondoTapa = Math.max(0, datos.fondo - (holguraTapaCm * 2));
            piezas.push(crearPieza("Tapa de vidrio", medidaPedido(largoTapa, datos), medidaPedido(fondoTapa, datos), piezasTapa, datos.espesor, "filo_muerto", "Tapa dividida en " + piezasTapa + " pieza(s)", true));
        }
    }
    function areaPieza(pieza) {
        return (numero(pieza.largo) / 100) * (numero(pieza.ancho) / 100) * cantidadEntera(pieza.cantidad);
    }
    function resumen() {
        var datos = datosFormulario();
        var cantidadPeceras = datos.cantidad;
        var piezasPorPecera = piezas.reduce(function (total, pieza) { return total + cantidadEntera(pieza.cantidad); }, 0);
        var areaPorPecera = piezas.reduce(function (total, pieza) { return total + areaPieza(pieza); }, 0);
        var totalPiezas = piezasPorPecera * cantidadPeceras;
        var areaTotal = areaPorPecera * cantidadPeceras;
        var costoVidrio = areaTotal * datos.costo_m2;
        var costoCorte = totalPiezas * datos.costo_corte;
        var volumenLitros = (datos.largo * datos.fondo * datos.alto) / 1000;

        return {
            totalPiezas: totalPiezas,
            areaTotal: areaTotal,
            costoTotal: costoVidrio + costoCorte,
            volumenLitros: volumenLitros,
            cantidadPeceras: cantidadPeceras
        };
    }
    function calcular() {
        var r = resumen();
        el("peceras_kpi_piezas").textContent = r.totalPiezas.toString();
        el("peceras_kpi_area").textContent = formato(r.areaTotal, 4) + " m2";
        el("peceras_kpi_costo").textContent = dinero(r.costoTotal);
        el("peceras_kpi_volumen").textContent = formato(r.volumenLitros, 1) + " L";
    }
    function opcionesAcabado(actual) {
        var opciones = [
            ["filo_muerto", "Filo muerto / corte natural"],
            ["cantos_visibles", "Cantos visibles"],
            ["pulido_completo", "Pulido completo"],
            ["biselado", "Biselado"]
        ];
        return opciones.map(function (opcion) {
            return "<option value=\"" + opcion[0] + "\"" + (actual === opcion[0] ? " selected" : "") + ">" + opcion[1] + "</option>";
        }).join("");
    }
    function renderPiezas() {
        var body = el("peceras_piezas");
        if (!piezas.length) {
            body.innerHTML = "<tr><td colspan=\"9\" class=\"text-center text-muted py-8\">Captura medidas para generar piezas.</td></tr>";
            calcular();
            return;
        }
        var datos = datosFormulario();
        body.innerHTML = piezas.map(function (pieza) {
            return "<tr data-id=\"" + escapeHtml(pieza.id) + "\">" +
                "<td><input class=\"form-control peceras-pieza-nombre\" data-campo=\"nombre\" value=\"" + escapeHtml(pieza.nombre) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-campo=\"largo\" value=\"" + escapeHtml(formato(pieza.largo, 2)) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-campo=\"ancho\" value=\"" + escapeHtml(formato(pieza.ancho, 2)) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-campo=\"espesor\" value=\"" + escapeHtml(formato(pieza.espesor, 2)) + "\"></td>" +
                "<td><div class=\"input-group\"><button class=\"btn btn-light\" type=\"button\" data-pieza-step=\"-1\"><i class=\"bi bi-dash\"></i></button><input class=\"form-control text-center\" inputmode=\"numeric\" data-campo=\"cantidad\" value=\"" + escapeHtml(pieza.cantidad) + "\"><button class=\"btn btn-light\" type=\"button\" data-pieza-step=\"1\"><i class=\"bi bi-plus\"></i></button></div></td>" +
                "<td><span class=\"badge badge-light-primary fs-7\">" + escapeHtml(pieza.cantidad * datos.cantidad) + "</span></td>" +
                "<td><select class=\"form-select\" data-campo=\"acabado\">" + opcionesAcabado(pieza.acabado) + "</select></td>" +
                "<td><input class=\"form-control\" data-campo=\"observaciones\" value=\"" + escapeHtml(pieza.observaciones) + "\"></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-icon btn-light-danger\" type=\"button\" data-eliminar-pieza title=\"Quitar pieza\"><i class=\"bi bi-trash\"></i></button></td>" +
                "</tr>";
        }).join("");
        calcular();
    }
    function actualizarPiezaDesdeCampo(input) {
        var fila = input.closest("tr[data-id]");
        if (!fila) {
            return;
        }
        var pieza = piezas.find(function (item) { return item.id === fila.getAttribute("data-id"); });
        if (!pieza) {
            return;
        }
        var campo = input.getAttribute("data-campo");
        if (["largo", "ancho", "espesor"].indexOf(campo) >= 0) {
            pieza[campo] = Math.max(0, numero(input.value));
            input.value = formato(pieza[campo], 2);
        } else if (campo === "cantidad") {
            pieza[campo] = cantidadEntera(input.value);
            input.value = pieza[campo];
        } else {
            pieza[campo] = input.value;
        }
        calcular();
    }
    function textoPedido() {
        var datos = datosFormulario();
        var r = resumen();
        var lineas = [];
        lineas.push("PEDIDO DE VIDRIO - PECERA");
        lineas.push("Referencia: " + (datos.nombre || "Sin referencia"));
        lineas.push("Proveedor: " + (datos.proveedor || "Por definir"));
        lineas.push("Medidas pecera: " + formato(datos.largo, 2) + " x " + formato(datos.fondo, 2) + " x " + formato(datos.alto, 2) + " cm");
        lineas.push("Espesor: " + formato(datos.espesor, 2) + " mm | Cantidad peceras: " + datos.cantidad);
        lineas.push("Base: " + (datos.base === "interior" ? "interior" : "exterior") + " | Holgura: " + formato(datos.holgura, 2) + " mm");
        lineas.push("Refuerzos: " + etiquetaRefuerzos(datos.refuerzos) + " | Tapa: " + (datos.tapa === "si" ? datos.tapa_piezas + " pieza(s)" : "No"));
        lineas.push("");
        lineas.push("PIEZAS");
        piezas.forEach(function (pieza, index) {
            lineas.push((index + 1) + ". " + pieza.nombre + " - " + formato(pieza.largo, 2) + " x " + formato(pieza.ancho, 2) + " cm - " + formato(pieza.espesor, 2) + " mm - Cantidad por pecera: " + pieza.cantidad + " - Total: " + (pieza.cantidad * datos.cantidad) + " - " + etiquetaAcabado(pieza.acabado) + (pieza.observaciones ? " - " + pieza.observaciones : ""));
        });
        lineas.push("");
        lineas.push("Resumen: " + r.totalPiezas + " piezas | " + formato(r.areaTotal, 4) + " m2 | Estimado " + dinero(r.costoTotal));
        if (datos.observaciones) {
            lineas.push("");
            lineas.push("Observaciones: " + datos.observaciones);
        }
        return lineas.join("\n");
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: preparar un resumen tecnico para pegarlo como observacion o adjunto en Compras.
     * Impacto: Produccion/Peceras hacia Compras; no intenta crear solicitud porque Compras requiere SKU proveedor formal.
     * Contrato: devuelve texto plano autocontenido con cortes, m2 y costos estimados.
     */
    function textoParaSolicitudCompra() {
        var datos = datosFormulario();
        var r = resumen();
        return [
            "Solicitud tecnica desde Produccion > Peceras y vidrio",
            "Referencia: " + (datos.nombre || "Pecera sin referencia"),
            "Proveedor sugerido: " + (datos.proveedor || "Proveedor de vidrio por definir"),
            "Necesidad: pedir vidrio cortado para fabricar " + datos.cantidad + " pecera(s) de " + formato(datos.largo, 2) + " x " + formato(datos.fondo, 2) + " x " + formato(datos.alto, 2) + " cm.",
            "Espesor: " + formato(datos.espesor, 2) + " mm. Base: " + (datos.base === "interior" ? "interior" : "exterior") + ". Holgura: " + formato(datos.holgura, 2) + " mm.",
            "Refuerzos: " + etiquetaRefuerzos(datos.refuerzos) + ". Tapa: " + (datos.tapa === "si" ? datos.tapa_piezas + " pieza(s)." : "No."),
            "Totales estimados: " + r.totalPiezas + " piezas, " + formato(r.areaTotal, 4) + " m2, " + dinero(r.costoTotal) + ".",
            "",
            "Detalle de cortes:",
            piezas.map(function (pieza, index) {
                return (index + 1) + ". " + pieza.nombre + ": " + formato(pieza.largo, 2) + " x " + formato(pieza.ancho, 2) + " cm, " + formato(pieza.espesor, 2) + " mm, " + (pieza.cantidad * datos.cantidad) + " pza(s) total, " + etiquetaAcabado(pieza.acabado) + (pieza.observaciones ? ", " + pieza.observaciones : "");
            }).join("\n"),
            datos.observaciones ? "\nObservaciones proveedor: " + datos.observaciones : "",
            "\nNota ERP: este texto es paquete tecnico. La solicitud formal debe relacionarse con SKU/servicio de vidrio o corte configurado en Catalogo/Proveedores."
        ].join("\n");
    }
    function nombreArchivoSeguro(base, extension) {
        var nombre = String(base || "pedido-vidrio-pecera")
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-zA-Z0-9-_]+/g, "-")
            .replace(/^-+|-+$/g, "")
            .toLowerCase() || "pedido-vidrio-pecera";
        return nombre + "." + extension;
    }
    function descargarArchivo(nombre, contenido, tipo) {
        var blob = new Blob([contenido], {type: tipo});
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.href = url;
        link.download = nombre;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }
    function csvValue(value) {
        return "\"" + String(value == null ? "" : value).replace(/"/g, "\"\"") + "\"";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: exportar cortes a CSV para enviarlos, adjuntarlos o importarlos manualmente.
     * Impacto: Produccion/Peceras; salida local sin persistencia ni cambios en Compras.
     * Contrato: usa medidas en cm, espesor en mm y cantidad total considerando cantidad de peceras.
     */
    function exportarCsv() {
        var datos = datosFormulario();
        var filas = [["referencia", "proveedor", "pieza", "largo_cm", "ancho_cm", "espesor_mm", "cantidad_por_pecera", "cantidad_total", "acabado", "observaciones"]];
        piezas.forEach(function (pieza) {
            filas.push([
                datos.nombre || "",
                datos.proveedor || "",
                pieza.nombre,
                formato(pieza.largo, 2),
                formato(pieza.ancho, 2),
                formato(pieza.espesor, 2),
                pieza.cantidad,
                pieza.cantidad * datos.cantidad,
                etiquetaAcabado(pieza.acabado),
                pieza.observaciones || ""
            ]);
        });
        var csv = filas.map(function (fila) { return fila.map(csvValue).join(","); }).join("\r\n");
        descargarArchivo(nombreArchivoSeguro(datos.nombre || "pedido-vidrio-pecera", "csv"), csv, "text/csv;charset=utf-8");
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-03
     * Proposito: exportar un paquete estructurado para trazabilidad futura o carga manual.
     * Impacto: Produccion/Peceras; permite conservar evidencia fuera de localStorage.
     * Contrato: incluye cabecera, piezas editadas y resumen calculado en JSON.
     */
    function exportarJson() {
        var datos = datosFormulario();
        var payload = {
            formato: "erp_produccion_peceras_pedido_vidrio_v1",
            generado_en: new Date().toISOString(),
            cabecera: datos,
            resumen: resumen(),
            piezas: piezas.map(function (pieza) {
                return {
                    nombre: pieza.nombre,
                    largo_cm: numero(pieza.largo),
                    ancho_cm: numero(pieza.ancho),
                    espesor_mm: numero(pieza.espesor),
                    cantidad_por_pecera: cantidadEntera(pieza.cantidad),
                    cantidad_total: cantidadEntera(pieza.cantidad) * datos.cantidad,
                    acabado: etiquetaAcabado(pieza.acabado),
                    observaciones: pieza.observaciones || ""
                };
            })
        };
        descargarArchivo(nombreArchivoSeguro(datos.nombre || "pedido-vidrio-pecera", "json"), JSON.stringify(payload, null, 2), "application/json;charset=utf-8");
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: restaurar paquetes JSON exportados por la calculadora de peceras.
     * Impacto: Produccion/Peceras; facilita mover pedidos entre equipos o conservar evidencia.
     * Contrato: solo acepta formato `erp_produccion_peceras_pedido_vidrio_v1` y no escribe en servidor.
     */
    function importarJsonArchivo(archivo) {
        if (!archivo) {
            return;
        }
        var lector = new FileReader();
        lector.onload = function () {
            try {
                var payload = JSON.parse(String(lector.result || ""));
                if (!payload || payload.formato !== "erp_produccion_peceras_pedido_vidrio_v1") {
                    throw new Error("El archivo no corresponde a un pedido de vidrio de peceras.");
                }
                aplicarDatos(payload.cabecera || {});
                piezas = Array.isArray(payload.piezas) ? payload.piezas.map(function (pieza) {
                    return crearPieza(
                        pieza.nombre || "Pieza importada",
                        pieza.largo_cm,
                        pieza.ancho_cm,
                        pieza.cantidad_por_pecera,
                        pieza.espesor_mm,
                        normalizarAcabado(pieza.acabado),
                        pieza.observaciones || "",
                        false
                    );
                }) : [];
                renderPiezas();
                toast("Pedido importado correctamente.");
            } catch (e) {
                toast(e.message || "No fue posible importar el archivo.", "warning");
            } finally {
                el("peceras_importar_json_archivo").value = "";
            }
        };
        lector.readAsText(archivo);
    }
    function etiquetaAcabado(valor) {
        var mapa = {filo_muerto: "Filo muerto / corte natural", sin_pulir: "Filo muerto / corte natural", cantos_visibles: "Cantos visibles", pulido_completo: "Pulido completo", biselado: "Biselado"};
        return mapa[valor] || valor || "";
    }
    function normalizarAcabado(valor) {
        var texto = String(valor || "").toLowerCase();
        if (texto === "sin pulir" || texto === "filo muerto" || texto === "filo muerto / corte natural") { return "filo_muerto"; }
        if (texto === "cantos visibles") { return "cantos_visibles"; }
        if (texto === "pulido completo") { return "pulido_completo"; }
        if (texto === "biselado") { return "biselado"; }
        return ["filo_muerto", "sin_pulir", "cantos_visibles", "pulido_completo", "biselado"].indexOf(texto) >= 0 ? (texto === "sin_pulir" ? "filo_muerto" : texto) : "filo_muerto";
    }
    function etiquetaRefuerzos(valor) {
        var mapa = {ninguno: "Sin refuerzos", frente_fondo: "Frente y fondo", perimetral: "Perimetral"};
        return mapa[valor] || valor || "Sin refuerzos";
    }
    function prepararImpresion() {
        var datos = datosFormulario();
        var r = resumen();
        var rows = piezas.map(function (pieza, index) {
            return "<tr>" +
                "<td>" + (index + 1) + "</td>" +
                "<td>" + escapeHtml(pieza.nombre) + "</td>" +
                "<td>" + escapeHtml(formato(pieza.largo, 2)) + " x " + escapeHtml(formato(pieza.ancho, 2)) + " cm</td>" +
                "<td>" + escapeHtml(formato(pieza.espesor, 2)) + " mm</td>" +
                "<td>" + escapeHtml(pieza.cantidad) + "</td>" +
                "<td>" + escapeHtml(pieza.cantidad * datos.cantidad) + "</td>" +
                "<td>" + escapeHtml(etiquetaAcabado(pieza.acabado)) + "</td>" +
                "<td>" + escapeHtml(pieza.observaciones) + "</td>" +
                "</tr>";
        }).join("");
        el("peceras_print_area").innerHTML =
            "<h1 style=\"font-size:22px;margin:0 0 8px\">Pedido de vidrio - Pecera</h1>" +
            "<div style=\"font-size:13px;margin-bottom:14px\">Referencia: <strong>" + escapeHtml(datos.nombre || "Sin referencia") + "</strong> | Proveedor: <strong>" + escapeHtml(datos.proveedor || "Por definir") + "</strong></div>" +
            "<div style=\"font-size:13px;margin-bottom:14px\">Medidas: " + escapeHtml(formato(datos.largo, 2)) + " x " + escapeHtml(formato(datos.fondo, 2)) + " x " + escapeHtml(formato(datos.alto, 2)) + " cm | Espesor: " + escapeHtml(formato(datos.espesor, 2)) + " mm | Cantidad: " + escapeHtml(datos.cantidad) + " | Refuerzos: " + escapeHtml(etiquetaRefuerzos(datos.refuerzos)) + " | Tapa: " + escapeHtml(datos.tapa === "si" ? datos.tapa_piezas + " pieza(s)" : "No") + "</div>" +
            "<table style=\"width:100%;border-collapse:collapse;font-size:12px\" border=\"1\" cellpadding=\"6\"><thead><tr><th>#</th><th>Pieza</th><th>Medida</th><th>Espesor</th><th>Por pecera</th><th>Total</th><th>Acabado</th><th>Observaciones</th></tr></thead><tbody>" + rows + "</tbody></table>" +
            "<div style=\"font-size:13px;margin-top:14px\"><strong>Resumen:</strong> " + escapeHtml(r.totalPiezas) + " piezas | " + escapeHtml(formato(r.areaTotal, 4)) + " m2 | Estimado " + escapeHtml(dinero(r.costoTotal)) + "</div>" +
            (datos.observaciones ? "<p style=\"font-size:13px;margin-top:10px\"><strong>Observaciones:</strong> " + escapeHtml(datos.observaciones) + "</p>" : "");
    }
    function cargarBorradores() {
        try {
            borradores = JSON.parse(localStorage.getItem(storageKey) || "[]");
        } catch (e) {
            borradores = [];
        }
        var select = el("peceras_borradores");
        select.innerHTML = "<option value=\"\">Borradores locales</option>" + borradores.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id) + "\">" + escapeHtml(item.nombre || "Pecera sin nombre") + "</option>";
        }).join("");
    }
    function guardarBorrador() {
        var datos = datosFormulario();
        var id = el("peceras_borradores").value || uuid();
        var nombre = datos.nombre || ("Pecera " + formato(datos.largo, 0) + "x" + formato(datos.fondo, 0) + "x" + formato(datos.alto, 0));
        var payload = {id: id, nombre: nombre, fecha: new Date().toISOString(), datos: datos, piezas: piezas};
        borradores = borradores.filter(function (item) { return item.id !== id; });
        borradores.unshift(payload);
        borradores = borradores.slice(0, 20);
        localStorage.setItem(storageKey, JSON.stringify(borradores));
        cargarBorradores();
        el("peceras_borradores").value = id;
        toast("Borrador guardado localmente.");
    }
    function aplicarDatos(datos) {
        el("peceras_nombre").value = datos.nombre || "";
        el("peceras_proveedor").value = datos.proveedor || "";
        el("peceras_largo").value = datos.largo || 60;
        el("peceras_fondo").value = datos.fondo || 40;
        el("peceras_alto").value = datos.alto || 40;
        el("peceras_espesor").value = datos.espesor || 5;
        el("peceras_cantidad").value = datos.cantidad || 1;
        el("peceras_base").value = datos.base || "interior";
        el("peceras_holgura").value = datos.holgura == null ? 2 : datos.holgura;
        el("peceras_descuento_corte").value = datos.descuento_corte == null ? 2 : datos.descuento_corte;
        el("peceras_refuerzos").value = datos.refuerzos || "ninguno";
        el("peceras_refuerzo_ancho").value = datos.refuerzo_ancho == null ? 5 : datos.refuerzo_ancho;
        el("peceras_tapa").value = datos.tapa || "no";
        el("peceras_tapa_piezas").value = datos.tapa_piezas || 1;
        el("peceras_costo_m2").value = datos.costo_m2 || "";
        el("peceras_costo_corte").value = datos.costo_corte || "";
        el("peceras_observaciones").value = datos.observaciones || "";
    }
    function cargarBorrador(id) {
        var item = borradores.find(function (borrador) { return borrador.id === id; });
        if (!item) {
            return;
        }
        aplicarDatos(item.datos || {});
        piezas = Array.isArray(item.piezas) ? item.piezas : [];
        renderPiezas();
    }
    function nuevo() {
        el("peceras_borradores").value = "";
        aplicarDatos({largo: 60, fondo: 40, alto: 40, espesor: 5, cantidad: 1, base: "interior", holgura: 2, descuento_corte: 2, refuerzos: "ninguno", refuerzo_ancho: 5, tapa: "no", tapa_piezas: 1});
        generarPiezas();
    }
    function regenerarPiezasSuave() {
        clearTimeout(window.pecerasRegenerarTimer);
        window.pecerasRegenerarTimer = setTimeout(generarPiezas, 180);
    }
    function registrarEventos() {
        ["peceras_largo", "peceras_fondo", "peceras_alto", "peceras_holgura", "peceras_descuento_corte", "peceras_refuerzo_ancho"].forEach(function (id) {
            el(id).addEventListener("input", regenerarPiezasSuave);
            el(id).addEventListener("change", generarPiezas);
        });
        ["peceras_espesor", "peceras_base", "peceras_refuerzos", "peceras_tapa", "peceras_tapa_piezas"].forEach(function (id) {
            el(id).addEventListener("change", generarPiezas);
        });
        ["peceras_cantidad", "peceras_costo_m2", "peceras_costo_corte"].forEach(function (id) {
            el(id).addEventListener("input", function () { renderPiezas(); calcular(); });
            el(id).addEventListener("change", function () { renderPiezas(); calcular(); });
        });
        document.addEventListener("click", function (event) {
            var stepper = event.target.closest("[data-stepper]");
            if (stepper) {
                var input = el(stepper.getAttribute("data-stepper"));
                input.value = Math.max(1, cantidadEntera(input.value) + Number(stepper.getAttribute("data-delta") || 0));
                if (input.id === "peceras_tapa_piezas") {
                    generarPiezas();
                } else {
                    renderPiezas();
                    calcular();
                }
                return;
            }
            var piezaStep = event.target.closest("[data-pieza-step]");
            if (piezaStep) {
                var fila = piezaStep.closest("tr[data-id]");
                var inputCantidad = fila ? fila.querySelector("[data-campo='cantidad']") : null;
                if (inputCantidad) {
                    inputCantidad.value = Math.max(1, cantidadEntera(inputCantidad.value) + Number(piezaStep.getAttribute("data-pieza-step") || 0));
                    actualizarPiezaDesdeCampo(inputCantidad);
                }
                return;
            }
            var eliminar = event.target.closest("[data-eliminar-pieza]");
            if (eliminar) {
                var row = eliminar.closest("tr[data-id]");
                piezas = piezas.filter(function (pieza) { return pieza.id !== row.getAttribute("data-id"); });
                renderPiezas();
                return;
            }
        });
        el("peceras_piezas").addEventListener("change", function (event) {
            if (event.target.matches("[data-campo]")) {
                actualizarPiezaDesdeCampo(event.target);
            }
        });
        el("peceras_piezas").addEventListener("input", function (event) {
            if (event.target.matches("select[data-campo], input[data-campo='nombre'], input[data-campo='observaciones']")) {
                actualizarPiezaDesdeCampo(event.target);
            }
        });
        el("peceras_agregar_pieza").addEventListener("click", function () {
            var datos = datosFormulario();
            piezas.push(crearPieza("Pieza manual", 0, 0, 1, datos.espesor || 5, "filo_muerto", "", false));
            renderPiezas();
        });
        el("peceras_copiar_texto").addEventListener("click", function () {
            var texto = textoPedido();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(function () { toast("Pedido copiado al portapapeles."); });
            } else {
                window.prompt("Copia el pedido:", texto);
            }
        });
        el("peceras_copiar_solicitud").addEventListener("click", function () {
            var texto = textoParaSolicitudCompra();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(function () { toast("Texto para solicitud copiado."); });
            } else {
                window.prompt("Copia el texto para solicitud:", texto);
            }
        });
        el("peceras_exportar_csv").addEventListener("click", exportarCsv);
        el("peceras_exportar_json").addEventListener("click", exportarJson);
        el("peceras_importar_json").addEventListener("click", function () {
            el("peceras_importar_json_archivo").click();
        });
        el("peceras_importar_json_archivo").addEventListener("change", function () {
            importarJsonArchivo(this.files && this.files[0]);
        });
        el("peceras_imprimir").addEventListener("click", function () {
            prepararImpresion();
            window.print();
        });
        el("peceras_guardar_local").addEventListener("click", guardarBorrador);
        el("peceras_nuevo").addEventListener("click", nuevo);
        el("peceras_borradores").addEventListener("change", function () { cargarBorrador(this.value); });
    }
    document.addEventListener("DOMContentLoaded", function () {
        registrarEventos();
        cargarBorradores();
        var perfilUrl = new URLSearchParams(window.location.search).get("perfil");
        if (perfilUrl) {
            el("peceras_borradores").value = perfilUrl;
            cargarBorrador(perfilUrl);
        } else {
            generarPiezas();
        }
    });
})();
