"use strict";
(function () {
    var endpoints = {perfiles: "/produccion/peceras_perfiles_listar_erp", pedidos: "/produccion/pedidos_vidrio_listar_erp", guardarPedido: "/produccion/pedido_vidrio_guardar_erp"};
    var perfiles = [];
    var items = [];
    var configsHoja = {};
    var extras = [];
    var pedidosGuardados = [];
    var planProveedor = [];

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
    function entero(value) {
        return Math.max(1, Math.round(numero(value) || 1));
    }
    function formato(value, decimales) {
        return Number(numero(value).toFixed(decimales == null ? 2 : decimales)).toString();
    }
    function csvValue(value) {
        return "\"" + String(value == null ? "" : value).replace(/"/g, "\"\"") + "\"";
    }
    function descargarArchivo(nombre, contenido) {
        var blob = new Blob([contenido], {type: "text/csv;charset=utf-8"});
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.href = url;
        link.download = nombre;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }
    function cargarPerfiles() {
        return fetch(endpoints.perfiles, {credentials: "same-origin", headers: {"Accept": "application/json"}}).then(jsonResponse).then(function (respuesta) {
            perfiles = respuesta && respuesta.depurar && Array.isArray(respuesta.depurar.perfiles) ? respuesta.depurar.perfiles : [];
            el("peceras_pedido_perfil").innerHTML = perfiles.length
                ? perfiles.map(function (perfil) { return "<option value=\"" + escapeHtml(perfil.id) + "\">" + escapeHtml([perfil.codigo, perfil.nombre || "Pecera sin nombre"].filter(Boolean).join(" - ")) + "</option>"; }).join("")
                : "<option value=\"\">Sin perfiles guardados</option>";
            cargarCatalogoFaltantes();
        });
    }
    function cargarPedidosGuardados() {
        return fetch(endpoints.pedidos, {credentials: "same-origin", headers: {"Accept": "application/json"}}).then(jsonResponse).then(function (respuesta) {
            pedidosGuardados = respuesta && respuesta.depurar && Array.isArray(respuesta.depurar.pedidos) ? respuesta.depurar.pedidos : [];
        });
    }
    function jsonResponse(response) {
        if (!response.ok) { throw new Error("No fue posible consultar Produccion."); }
        return response.json();
    }
    function perfilPorId(id) {
        return perfiles.find(function (perfil) { return String(perfil.id) === String(id); });
    }
    function bloqueParaCodigo(codigo) {
        var texto = String(codigo || "").toUpperCase();
        return planProveedor.find(function (bloque) {
            return (bloque.perfiles || []).some(function (perfil) {
                return texto && String(perfil || "").toUpperCase().indexOf(texto) >= 0;
            });
        }) || null;
    }
    function medidasHojaBloque(bloque) {
        var coincidencia = String((bloque && bloque.hoja) || "").match(/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)/i);
        return coincidencia ? {largo: Number(coincidencia[1]), ancho: Number(coincidencia[2])} : null;
    }
    function claveHojaPieza(pieza) {
        return pieza.perfil_codigo || pieza.bloque_id || formato(pieza.espesor, 2);
    }
    function codigoBasePieza(pieza) {
        return String(pieza.perfil_codigo || "").replace(/\s+\(faltante\)$/i, "");
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-24
     * Proposito: permitir faltantes por pieza vinculados al perfil maestro.
     * Impacto: Produccion/Pedido multiple; evita pedir juegos completos cuando solo faltan piezas.
     * Contrato: solo prepara lineas del pedido; no modifica existencias ni crea compras.
     */
    function cargarCatalogoFaltantes(codigoPreferido) {
        var selectorPerfil = el("peceras_faltante_perfil");
        if (!selectorPerfil) {
            return;
        }
        selectorPerfil.innerHTML = perfiles.length
            ? perfiles.map(function (perfil) { return "<option value=\"" + escapeHtml(perfil.id) + "\">" + escapeHtml(perfil.codigo || perfil.nombre || "Perfil") + "</option>"; }).join("")
            : "<option value=\"\">Sin perfiles guardados</option>";
        if (codigoPreferido) {
            var perfilPreferido = perfiles.find(function (perfil) { return String(perfil.codigo || "").toUpperCase() === String(codigoPreferido).toUpperCase(); });
            if (perfilPreferido) {
                selectorPerfil.value = perfilPreferido.id;
            }
        }
        cargarPiezasFaltantes();
    }
    function cargarPiezasFaltantes() {
        var perfil = perfilPorId(el("peceras_faltante_perfil").value);
        var piezas = perfil && Array.isArray(perfil.piezas) ? perfil.piezas : [];
        el("peceras_faltante_pieza").innerHTML = piezas.length
            ? piezas.map(function (pieza, index) { return "<option value=\"" + escapeHtml(index) + "\">" + escapeHtml(pieza.nombre || "Pieza") + "</option>"; }).join("")
            : "<option value=\"\">Sin piezas configuradas</option>";
        mostrarMedidaFaltante();
    }
    function mostrarMedidaFaltante() {
        var perfil = perfilPorId(el("peceras_faltante_perfil").value);
        var piezas = perfil && Array.isArray(perfil.piezas) ? perfil.piezas : [];
        var pieza = piezas[Number(el("peceras_faltante_pieza").value)] || null;
        el("peceras_faltante_medida").textContent = pieza
            ? formato(pieza.largo || pieza.largo_cm, 2) + " x " + formato(pieza.ancho || pieza.ancho_cm, 2) + " cm | " + formato((perfil && perfil.datos && perfil.datos.espesor) || pieza.espesor || pieza.espesor_mm, 2) + " mm"
            : "Selecciona una pieza";
    }
    function uuid() {
        return "pedido_item_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2, 8);
    }
    function areaPieza(pieza, cantidadPerfiles) {
        var cantidad = entero(pieza.cantidad || pieza.cantidad_por_pecera || 1) * cantidadPerfiles;
        return (numero(pieza.largo || pieza.largo_cm) / 100) * (numero(pieza.ancho || pieza.ancho_cm) / 100) * cantidad;
    }
    function piezasPedido() {
        var salida = [];
        items.forEach(function (item) {
            var perfil = item.perfil;
            var d = perfil.datos || {};
            var bloque = bloqueParaCodigo(perfil.codigo || perfil.nombre);
            var piezas = Array.isArray(perfil.piezas) ? perfil.piezas : [];
            piezas.forEach(function (pieza) {
                salida.push({
                    perfil_id: perfil.id,
                    perfil_codigo: perfil.codigo || perfil.nombre || "Perfil",
                    perfil_nombre: perfil.nombre || "Pecera sin nombre",
                    bloque_id: bloque ? bloque.id : "",
                    bloque_hoja: bloque ? bloque.hoja : "",
                    pieza: pieza.nombre || "Pieza",
                    largo: numero(pieza.largo || pieza.largo_cm),
                    ancho: numero(pieza.ancho || pieza.ancho_cm),
                    // El espesor del perfil es la regla vigente; las variantes historicas
                    // de cada pieza no deben crear grupos de 10, 5 o 3 mm por accidente.
                    espesor: numero(d.espesor || perfil.espesor_mm || pieza.espesor || pieza.espesor_mm),
                    cantidad: entero(pieza.cantidad || pieza.cantidad_por_pecera || 1) * item.cantidad,
                    area: areaPieza(pieza, item.cantidad)
                });
            });
        });
        extras.forEach(function (pieza) {
            var bloque = bloqueParaCodigo(pieza.perfil_codigo);
            salida.push({
                perfil_id: pieza.perfil_id || "extra",
                perfil_codigo: pieza.perfil_codigo ? pieza.perfil_codigo + " (faltante)" : "Extra",
                perfil_nombre: pieza.perfil_codigo ? pieza.perfil_codigo + " (faltante)" : "Extra",
                bloque_id: bloque ? bloque.id : "",
                bloque_hoja: bloque ? bloque.hoja : "",
                pieza: pieza.nombre || "Pieza extra",
                largo: numero(pieza.largo),
                ancho: numero(pieza.ancho),
                espesor: numero(pieza.espesor),
                cantidad: entero(pieza.cantidad),
                area: (numero(pieza.largo) / 100) * (numero(pieza.ancho) / 100) * entero(pieza.cantidad),
                extra_id: pieza.id
            });
        });
        return salida;
    }
    function configDefaultHoja(espesor) {
        var espesorMm = numero(espesor);
        var hojaHistorica = espesorMm >= 5.5 ? {largo: 360, ancho: 260} : {largo: 260, ancho: 180};
        return {
            largo: hojaHistorica.largo,
            ancho: hojaHistorica.ancho,
            merma: Math.max(0, numero(el("peceras_pedido_merma").value)),
            separacion: Math.max(0, numero(el("peceras_pedido_separacion").value))
        };
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-24
     * Proposito: separar la medida fisica capturada de la direccion operativa de corte.
     * Impacto: Produccion/Pedido multiple; las bandas avanzan por el lado largo y el corte recto cruza el lado corto.
     * Contrato: devuelve siempre largoAvance >= anchoCorte, sin cambiar el area fisica de la hoja.
     */
    function normalizarDireccionHoja(largo, ancho) {
        var ladoA = numero(largo);
        var ladoB = numero(ancho);
        return {
            largoAvance: Math.max(ladoA, ladoB),
            anchoCorte: Math.min(ladoA, ladoB)
        };
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: detectar tipos de hoja por espesor y permitir medidas distintas por cada grupo.
     * Impacto: Produccion/Pedido multiple vidrio; evita asumir una sola hoja cuando el pedido mezcla espesores.
     * Contrato: la deteccion usa el espesor de cada pieza; las medidas se capturan localmente en la vista.
     */
    function sincronizarTiposHoja(piezas) {
        var espesores = {};
        piezas.forEach(function (pieza) {
            espesores[claveHojaPieza(pieza)] = pieza.espesor;
        });
        Object.keys(espesores).forEach(function (key) {
            if (!configsHoja[key]) {
                configsHoja[key] = configDefaultHoja(Number(key));
                var bloque = bloqueParaCodigo(key);
                var hojaBloque = medidasHojaBloque(bloque);
                if (hojaBloque) {
                    configsHoja[key].largo = hojaBloque.largo;
                    configsHoja[key].ancho = hojaBloque.ancho;
                }
            }
        });
        Object.keys(configsHoja).forEach(function (key) {
            if (!espesores[key]) {
                delete configsHoja[key];
            }
        });
    }
    function renderTiposHoja() {
        var body = el("peceras_pedido_tipos_hoja");
        var keys = Object.keys(configsHoja).sort();
        body.innerHTML = keys.length ? keys.map(function (key) {
            var c = configsHoja[key];
            var bloque = bloqueParaCodigo(key);
            var etiqueta = bloque ? key + " | " + bloque.espesor + " mm | " + bloque.hoja : key + " mm";
            return "<tr data-espesor=\"" + escapeHtml(key) + "\">" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(etiqueta) + "</span></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-hoja-campo=\"largo\" value=\"" + escapeHtml(formato(c.largo, 2)) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-hoja-campo=\"ancho\" value=\"" + escapeHtml(formato(c.ancho, 2)) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-hoja-campo=\"merma\" value=\"" + escapeHtml(formato(c.merma, 2)) + "\"></td>" +
                "<td><input class=\"form-control\" inputmode=\"decimal\" data-hoja-campo=\"separacion\" value=\"" + escapeHtml(formato(c.separacion, 2)) + "\"></td>" +
                "</tr>";
        }).join("") : "<tr><td colspan=\"5\" class=\"text-muted text-center py-5\">Agrega perfiles para detectar tipos de hoja.</td></tr>";
    }
    function resumenHojas() {
        var tipoAcomodo = el("peceras_pedido_acomodo").value;
        var grupos = {};
        piezasPedido().forEach(function (pieza) {
            var key = claveHojaPieza(pieza);
            if (!grupos[key]) {
                grupos[key] = {clave: key, perfil_codigo: codigoBasePieza(pieza), bloque_id: pieza.bloque_id || "", bloque_hoja: pieza.bloque_hoja || "", espesor: pieza.espesor, piezas: 0, area: 0, alertas: [], piezasDetalle: []};
            }
            grupos[key].piezas += pieza.cantidad;
            grupos[key].area += pieza.area;
            grupos[key].piezasDetalle.push(pieza);
        });
        return Object.keys(grupos).sort().map(function (key) {
            var g = grupos[key];
            var config = configsHoja[key] || configDefaultHoja(g.espesor);
            var direccionHoja = normalizarDireccionHoja(config.largo, config.ancho);
            var hojaLargo = direccionHoja.largoAvance;
            var hojaAncho = direccionHoja.anchoCorte;
            var merma = Math.max(0, numero(config.merma)) / 100;
            var separacionCm = Math.max(0, numero(config.separacion)) / 10;
            var areaHoja = (hojaLargo / 100) * (hojaAncho / 100);
            g.hojaLargo = hojaLargo;
            g.hojaAncho = hojaAncho;
            g.separacionCm = separacionCm;
            g.bloque_hoja_fijada = Boolean(g.bloque_hoja);
            g.areaConMerma = g.area * (1 + merma);
            g.tipoAcomodo = tipoAcomodo;
            g.piezasDetalle.forEach(function (pieza) {
                var piezaMayor = Math.max(pieza.largo, pieza.ancho);
                var piezaMenor = Math.min(pieza.largo, pieza.ancho);
                var hojaMayor = Math.max(hojaLargo, hojaAncho);
                var hojaMenor = Math.min(hojaLargo, hojaAncho);
                if (piezaMayor > hojaMayor || piezaMenor > hojaMenor) {
                    g.alertas.push(pieza.pieza + " excede hoja");
                }
            });
            if (tipoAcomodo === "columnas_ancho") {
                g.acomodo = acomodarColumnasAncho(g.piezasDetalle, hojaLargo, hojaAncho, separacionCm);
                calcularMetricasAcomodo(g);
                g.hojas = g.acomodo.hojas.length;
                g.alertas = g.alertas.concat(g.acomodo.alertas);
                g.descripcionAcomodo = "Bandas ancho: " + g.acomodo.hojas.map(function (hoja, index) {
                    return "H" + (index + 1) + " " + hoja.piezas.length + " pza";
                }).join(", ");
            } else if (tipoAcomodo === "filas_rotacion") {
                g.acomodo = acomodarFilas(g.piezasDetalle, hojaLargo, hojaAncho, separacionCm);
                calcularMetricasAcomodo(g);
                g.hojas = g.acomodo.hojas.length;
                g.alertas = g.alertas.concat(g.acomodo.alertas);
                g.descripcionAcomodo = "Filas: " + g.acomodo.hojas.map(function (hoja, index) {
                    return "H" + (index + 1) + " " + hoja.piezas.length + " pza";
                }).join(", ");
            } else {
                g.hojas = areaHoja > 0 ? Math.ceil(g.areaConMerma / areaHoja) : 0;
                g.acomodo = null;
                g.descripcionAcomodo = formato(g.areaConMerma, 4) + " m2 con merma";
            }
            if (g.espesor >= 5.5 && tipoAcomodo !== "area" && !g.bloque_hoja_fijada) {
                aplicarMejorHojaSeisMm(g, tipoAcomodo, separacionCm, config);
            }
            var bloquePlan = bloqueParaCodigo(g.perfil_codigo);
            g.hojasSolicitadas = bloquePlan ? entero(bloquePlan.hojas_reservadas) : 0;
            if (g.hojasSolicitadas > 0 && g.hojas > g.hojasSolicitadas) {
                g.alertas.push("Requiere " + g.hojas + " hojas; se indicaron " + g.hojasSolicitadas + ". Las hojas adicionales deben confirmarse con el proveedor.");
            }
            return g;
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-06
     * Proposito: calcular aprovechamiento y desperdicio por hoja visual.
     * Impacto: Produccion/Pedido multiple vidrio; permite comparar pedidos guardados por porcentaje de desperdicio.
     * Contrato: usa area real de piezas colocadas contra area total de hoja por espesor.
     */
    function calcularMetricasAcomodo(grupo) {
        var areaHoja = (numero(grupo.hojaLargo) / 100) * (numero(grupo.hojaAncho) / 100);
        grupo.acomodo.hojas.forEach(function (hoja) {
            var areaUsada = hoja.piezas.reduce(function (total, pieza) {
                return total + ((numero(pieza.largo) / 100) * (numero(pieza.ancho) / 100));
            }, 0);
            var desperdicio = areaHoja > 0 ? Math.max(0, ((areaHoja - areaUsada) / areaHoja) * 100) : 0;
            hoja.areaUsada = areaUsada;
            hoja.areaHoja = areaHoja;
            hoja.desperdicio = desperdicio;
            hoja.aprovechamiento = Math.max(0, 100 - desperdicio);
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: comparar las dos medidas de hoja historicas disponibles para vidrio de 6 mm.
     * Impacto: Produccion/Pedido multiple vidrio; prioriza desperdicio real del acomodo sobre area teorica.
     * Contrato: prueba 260x180 y 360x260 con la misma lista de piezas, bandas y separacion.
     */
    function aplicarMejorHojaSeisMm(grupo, tipoAcomodo, separacionCm, configActual) {
        var candidatos = [
            {largo: 260, ancho: 180},
            {largo: 360, ancho: 260}
        ];
        var resultados = candidatos.map(function (candidato) {
            var acomodo = tipoAcomodo === "columnas_ancho"
                ? acomodarColumnasAncho(grupo.piezasDetalle, candidato.largo, candidato.ancho, separacionCm)
                : acomodarFilas(grupo.piezasDetalle, candidato.largo, candidato.ancho, separacionCm);
            var evaluacion = {hojaLargo: candidato.largo, hojaAncho: candidato.ancho, acomodo: acomodo};
            calcularMetricasAcomodo(evaluacion);
            var areaHojaTotal = evaluacion.acomodo.hojas.reduce(function (total, hoja) { return total + hoja.areaHoja; }, 0);
            var areaUsadaTotal = evaluacion.acomodo.hojas.reduce(function (total, hoja) { return total + hoja.areaUsada; }, 0);
            evaluacion.desperdicioGlobal = areaHojaTotal > 0 ? Math.max(0, ((areaHojaTotal - areaUsadaTotal) / areaHojaTotal) * 100) : 100;
            evaluacion.hojas = evaluacion.acomodo.hojas.length;
            evaluacion.alertas = evaluacion.acomodo.alertas || [];
            return evaluacion;
        });
        resultados.sort(function (a, b) {
            var diferencia = a.desperdicioGlobal - b.desperdicioGlobal;
            return Math.abs(diferencia) > 0.0001 ? diferencia : a.hojas - b.hojas;
        });
        var mejor = resultados[0];
        grupo.hojaLargo = mejor.hojaLargo;
        grupo.hojaAncho = mejor.hojaAncho;
        grupo.acomodo = mejor.acomodo;
        grupo.hojas = mejor.hojas;
        grupo.alertas = (grupo.alertas || []).filter(function (alerta) { return alerta.indexOf("excede hoja") < 0; }).concat(mejor.alertas);
        grupo.alternativasHoja = resultados.map(function (resultado) {
            return {largo: resultado.hojaLargo, ancho: resultado.hojaAncho, hojas: resultado.hojas, desperdicio: resultado.desperdicioGlobal};
        });
        grupo.descripcionAcomodo = (tipoAcomodo === "columnas_ancho" ? "Bandas ancho" : "Filas") + " recomendado " + formato(mejor.hojaLargo, 0) + "x" + formato(mejor.hojaAncho, 0) + " cm: " + mejor.acomodo.hojas.map(function (hoja, index) {
            return "H" + (index + 1) + " " + hoja.piezas.length + " pza";
        }).join(", ");
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: estimar acomodo siguiendo el criterio operativo del proveedor: bandas alineadas para cortar recto hacia el ancho corto.
     * Impacto: Produccion/Pedido multiple vidrio; acerca el visual al proceso real de corte manual/asistido.
     * Contrato: heuristica first-fit por bandas; cada banda conserva una linea recta de corte sobre todo el ancho de hoja.
     */
    function acomodarColumnasAncho(piezasAgrupadas, hojaLargo, hojaAncho, separacionCm) {
        var piezas = [];
        var alertas = [];
        piezasAgrupadas.forEach(function (pieza) {
            for (var i = 0; i < pieza.cantidad; i++) {
                piezas.push({
                    perfil_codigo: pieza.perfil_codigo,
                    perfil_nombre: pieza.perfil_nombre,
                    pieza: pieza.pieza,
                    largo: pieza.largo,
                    ancho: pieza.ancho,
                    espesor: pieza.espesor
                });
            }
        });
        piezas.sort(function (a, b) {
            return Math.max(b.largo, b.ancho) - Math.max(a.largo, a.ancho) || (b.largo * b.ancho) - (a.largo * a.ancho);
        });

        var hojas = [];
        piezas.forEach(function (pieza) {
            var mejorIndice = -1;
            var mejorHoja = null;
            var mejorDesperdicio = Infinity;
            for (var h = 0; h < hojas.length; h++) {
                var candidata = JSON.parse(JSON.stringify(hojas[h]));
                if (colocarEnHojaColumnas(candidata, pieza, hojaLargo, hojaAncho, separacionCm)) {
                    var desperdicio = areaHojaMenosPiezas(candidata, hojaLargo, hojaAncho);
                    if (desperdicio < mejorDesperdicio) {
                        mejorIndice = h;
                        mejorHoja = candidata;
                        mejorDesperdicio = desperdicio;
                    }
                }
            }
            if (mejorIndice >= 0) {
                hojas[mejorIndice] = mejorHoja;
            } else {
                var hoja = {columnas: [], filas: [], piezas: []};
                hojas.push(hoja);
                if (!colocarEnHojaColumnas(hoja, pieza, hojaLargo, hojaAncho, separacionCm)) {
                    alertas.push(pieza.pieza + " no cabe en hoja " + formato(hojaLargo, 0) + "x" + formato(hojaAncho, 0));
                }
            }
        });
        return {hojas: hojas, alertas: alertas};
    }
    function areaHojaMenosPiezas(hoja, hojaLargo, hojaAncho) {
        var areaHoja = (hojaLargo / 100) * (hojaAncho / 100);
        var areaPiezas = (hoja.piezas || []).reduce(function (total, pieza) {
            return total + (numero(pieza.largo) / 100) * (numero(pieza.ancho) / 100);
        }, 0);
        return areaHoja - areaPiezas;
    }
    function colocarEnHojaColumnas(hoja, pieza, hojaLargo, hojaAncho, separacionCm) {
        var opciones = orientacionesPieza(pieza, hojaLargo, hojaAncho).sort(function (a, b) {
            // EasyCut prioriza que la dimension larga avance por la hoja;
            // solo se rota cuando la orientacion larga no cabe o desperdicia mas.
            return b.largo - a.largo || a.ancho - b.ancho;
        });
        for (var o = 0; o < opciones.length; o++) {
            var orientada = opciones[o];
            if (orientada.largo > hojaLargo || orientada.ancho > hojaAncho) {
                continue;
            }
            for (var c = 0; c < hoja.columnas.length; c++) {
                var columna = hoja.columnas[c];
                var altoUsado = columna.altoUsado > 0 ? columna.altoUsado + separacionCm : 0;
                if (altoUsado + orientada.ancho <= hojaAncho && orientada.largo <= columna.ancho) {
                    columna.piezas.push(Object.assign({}, pieza, orientada, {x: columna.x || 0, y: altoUsado, corteLargo: columna.ancho}));
                    columna.altoUsado = altoUsado + orientada.ancho;
                    hoja.piezas.push(Object.assign({}, pieza, orientada, {x: columna.x || 0, y: altoUsado, corteLargo: columna.ancho}));
                    return true;
                }
            }
            var anchoUsado = hoja.columnas.reduce(function (total, columna) {
                return total + columna.ancho;
            }, 0);
            if (hoja.columnas.length > 0) {
                anchoUsado += separacionCm * hoja.columnas.length;
            }
            if (anchoUsado + orientada.largo <= hojaLargo) {
                hoja.columnas.push({ancho: orientada.largo, x: anchoUsado, altoUsado: orientada.ancho, piezas: [Object.assign({}, pieza, orientada, {x: anchoUsado, y: 0, corteLargo: orientada.largo})]});
                hoja.piezas.push(Object.assign({}, pieza, orientada, {x: anchoUsado, y: 0, corteLargo: orientada.largo}));
                return true;
            }
        }
        return false;
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: estimar hojas reales por acomodo simple en filas con rotacion de piezas.
     * Impacto: Produccion/Pedido multiple vidrio; mejora la planeacion de hojas contra usar solo area.
     * Contrato: heuristica local first-fit por filas; no garantiza optimizacion industrial ni reemplaza nesting CAD/CAM.
     */
    function acomodarFilas(piezasAgrupadas, hojaLargo, hojaAncho, separacionCm) {
        var piezas = [];
        var alertas = [];
        piezasAgrupadas.forEach(function (pieza) {
            for (var i = 0; i < pieza.cantidad; i++) {
                piezas.push({
                    perfil_codigo: pieza.perfil_codigo,
                    perfil_nombre: pieza.perfil_nombre,
                    pieza: pieza.pieza,
                    largo: pieza.largo,
                    ancho: pieza.ancho,
                    espesor: pieza.espesor
                });
            }
        });
        piezas.sort(function (a, b) {
            return Math.max(b.largo, b.ancho) - Math.max(a.largo, a.ancho) || (b.largo * b.ancho) - (a.largo * a.ancho);
        });

        var hojas = [];
        piezas.forEach(function (pieza) {
            var mejorIndice = -1;
            var mejorHoja = null;
            var mejorDesperdicio = Infinity;
            for (var h = 0; h < hojas.length; h++) {
                var candidata = JSON.parse(JSON.stringify(hojas[h]));
                if (colocarEnHoja(candidata, pieza, hojaLargo, hojaAncho, separacionCm)) {
                    var desperdicio = areaHojaMenosPiezas(candidata, hojaLargo, hojaAncho);
                    if (desperdicio < mejorDesperdicio) {
                        mejorIndice = h;
                        mejorHoja = candidata;
                        mejorDesperdicio = desperdicio;
                    }
                }
            }
            if (mejorIndice >= 0) {
                hojas[mejorIndice] = mejorHoja;
            } else {
                var hoja = {filas: [], piezas: []};
                hojas.push(hoja);
                if (!colocarEnHoja(hoja, pieza, hojaLargo, hojaAncho, separacionCm)) {
                    alertas.push(pieza.pieza + " no cabe en hoja " + formato(hojaLargo, 0) + "x" + formato(hojaAncho, 0));
                }
            }
        });
        return {hojas: hojas, alertas: alertas};
    }
    function orientacionesPieza(pieza, hojaLargo, hojaAncho) {
        var opciones = [
            {largo: pieza.largo, ancho: pieza.ancho, rotada: false},
            {largo: pieza.ancho, ancho: pieza.largo, rotada: true}
        ];
        return opciones.filter(function (opcion, index) {
            return index === 0 || pieza.largo !== pieza.ancho;
        }).sort(function (a, b) {
            var aCabe = a.largo <= hojaLargo && a.ancho <= hojaAncho ? 0 : 1;
            var bCabe = b.largo <= hojaLargo && b.ancho <= hojaAncho ? 0 : 1;
            return aCabe - bCabe || b.largo - a.largo;
        });
    }
    function colocarEnHoja(hoja, pieza, hojaLargo, hojaAncho, separacionCm) {
        var opciones = orientacionesPieza(pieza, hojaLargo, hojaAncho);
        for (var o = 0; o < opciones.length; o++) {
            var orientada = opciones[o];
            if (orientada.largo > hojaLargo || orientada.ancho > hojaAncho) {
                continue;
            }
            for (var f = 0; f < hoja.filas.length; f++) {
                var fila = hoja.filas[f];
                var anchoUsado = fila.anchoUsado > 0 ? fila.anchoUsado + separacionCm : 0;
                if (anchoUsado + orientada.largo <= hojaLargo && orientada.ancho <= fila.alto) {
                    fila.piezas.push(Object.assign({}, pieza, orientada, {x: anchoUsado, y: fila.y || 0}));
                    fila.anchoUsado = anchoUsado + orientada.largo;
                    hoja.piezas.push(Object.assign({}, pieza, orientada, {x: anchoUsado, y: fila.y || 0}));
                    return true;
                }
            }
            var altoUsado = hoja.filas.reduce(function (total, fila) {
                return total + fila.alto;
            }, 0);
            if (hoja.filas.length > 0) {
                altoUsado += separacionCm * hoja.filas.length;
            }
            if (altoUsado + orientada.ancho <= hojaAncho) {
                hoja.filas.push({alto: orientada.ancho, y: altoUsado, anchoUsado: orientada.largo, piezas: [Object.assign({}, pieza, orientada, {x: 0, y: altoUsado})]});
                hoja.piezas.push(Object.assign({}, pieza, orientada, {x: 0, y: altoUsado}));
                return true;
            }
        }
        return false;
    }
    function render(mantenerTiposHoja) {
        var piezas = piezasPedido();
        sincronizarTiposHoja(piezas);
        if (!mantenerTiposHoja) {
            renderTiposHoja();
        }
        var hojas = resumenHojas();
        var area = piezas.reduce(function (total, pieza) { return total + pieza.area; }, 0);
        var totalPiezas = piezas.reduce(function (total, pieza) { return total + pieza.cantidad; }, 0);
        var totalHojas = hojas.reduce(function (total, grupo) { return total + grupo.hojas; }, 0);
        var desperdicioPromedio = desperdicioPromedioHojas(hojas);

        el("peceras_pedido_kpi_perfiles").textContent = items.length;
        el("peceras_pedido_kpi_piezas").textContent = totalPiezas;
        el("peceras_pedido_kpi_area").textContent = formato(area, 4) + " m2";
        el("peceras_pedido_kpi_hojas").textContent = totalHojas;
        el("peceras_pedido_kpi_desperdicio").textContent = formato(desperdicioPromedio, 1) + "%";
        renderPedidosGuardados();

        el("peceras_pedido_items").innerHTML = items.length ? items.map(function (item) {
            return "<div class=\"d-flex align-items-center justify-content-between border rounded p-3 mb-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(item.perfil.nombre || "Pecera sin nombre") + "</div><div class=\"text-muted\">" + escapeHtml(item.cantidad) + " perfil(es) en pedido</div></div>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-quitar-item=\"" + escapeHtml(item.id) + "\"><i class=\"bi bi-x-lg\"></i></button>" +
                "</div>";
        }).join("") : "<div class=\"text-muted py-5\">Agrega perfiles guardados al pedido.</div>";
        renderExtras();

        el("peceras_pedido_hojas").innerHTML = hojas.length ? hojas.map(function (grupo) {
            var etiquetaPerfil = grupo.perfil_codigo ? grupo.perfil_codigo + " | " : "";
            var hojasPlan = grupo.hojasSolicitadas > 0 ? " de " + grupo.hojasSolicitadas + " indicadas" : "";
            return "<tr><td>" + escapeHtml(etiquetaPerfil + formato(grupo.espesor, 2)) + " mm</td><td>" + escapeHtml(grupo.piezas) + "</td><td>" + escapeHtml(formato(grupo.area, 4)) + " m2</td><td>" + escapeHtml(grupo.descripcionAcomodo) + "</td><td><span class=\"badge " + (grupo.hojasSolicitadas > 0 && grupo.hojas > grupo.hojasSolicitadas ? "badge-light-danger" : "badge-light-primary") + " fs-7\">" + escapeHtml(grupo.hojas) + hojasPlan + "</span></td><td>" + escapeHtml(grupo.alertas.slice(0, 3).join(", ")) + "</td></tr>";
        }).join("") : "<tr><td colspan=\"6\" class=\"text-muted text-center py-5\">Sin perfiles en pedido.</td></tr>";
        renderDetalleAcomodo(hojas);

        el("peceras_pedido_piezas").innerHTML = piezas.length ? piezas.map(function (pieza) {
            return "<tr><td>" + escapeHtml(pieza.perfil_nombre) + "</td><td>" + escapeHtml(pieza.pieza) + "</td><td>" + escapeHtml(formato(pieza.largo, 2)) + " x " + escapeHtml(formato(pieza.ancho, 2)) + " cm</td><td>" + escapeHtml(formato(pieza.espesor, 2)) + " mm</td><td>" + escapeHtml(pieza.cantidad) + "</td><td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-extra-copiar=\"" + escapeHtml(pieza.perfil_nombre + "|" + pieza.pieza + "|" + pieza.largo + "|" + pieza.ancho + "|" + pieza.espesor) + "\"><i class=\"bi bi-plus-circle\"></i> Extra</button></td></tr>";
        }).join("") : "<tr><td colspan=\"6\" class=\"text-muted text-center py-5\">Sin piezas calculadas.</td></tr>";
    }
    function desperdicioPromedioHojas(hojas) {
        var totalAreaHoja = 0;
        var totalAreaUsada = 0;
        hojas.forEach(function (grupo) {
            if (!grupo.acomodo || !grupo.acomodo.hojas) {
                return;
            }
            grupo.acomodo.hojas.forEach(function (hoja) {
                totalAreaHoja += numero(hoja.areaHoja);
                totalAreaUsada += numero(hoja.areaUsada);
            });
        });
        if (totalAreaHoja <= 0) {
            return 0;
        }
        return Math.max(0, ((totalAreaHoja - totalAreaUsada) / totalAreaHoja) * 100);
    }
    function renderExtras() {
        el("peceras_extra_lista").innerHTML = extras.length ? extras.map(function (pieza) {
            var origen = pieza.tipo === "faltante" ? "Faltante " + (pieza.perfil_codigo || "perfil") + " / " : "";
            return "<div class=\"d-flex align-items-center justify-content-between border rounded p-3 mb-2\">" +
                "<div><span class=\"fw-bold\">" + escapeHtml(origen + pieza.nombre) + "</span><span class=\"text-muted ms-2\">" + escapeHtml(formato(pieza.largo, 2)) + " x " + escapeHtml(formato(pieza.ancho, 2)) + " cm, " + escapeHtml(formato(pieza.espesor, 2)) + " mm, " + escapeHtml(pieza.cantidad) + " pza</span></div>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-extra-quitar=\"" + escapeHtml(pieza.id) + "\"><i class=\"bi bi-x-lg\"></i></button>" +
                "</div>";
        }).join("") : "<div class=\"text-muted\">Sin piezas extra agregadas.</div>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-06
     * Proposito: agregar piezas extra al pedido multiple para aprovechar sobrantes de hoja.
     * Impacto: Produccion/Pedido multiple vidrio; las piezas extra entran al mismo acomodo visual por espesor.
     * Contrato: captura local sin persistencia en BD; se recalcula junto con perfiles.
     */
    function agregarExtra(datos) {
        if (numero(datos.largo) <= 0 || numero(datos.ancho) <= 0 || numero(datos.espesor) <= 0) {
            return;
        }
        extras.push({
            id: "extra_" + uuid(),
            nombre: datos.nombre || "Pieza extra",
            largo: numero(datos.largo),
            ancho: numero(datos.ancho),
            espesor: numero(datos.espesor),
            cantidad: entero(datos.cantidad)
        });
        render();
    }
    function agregarFaltante() {
        var perfil = perfilPorId(el("peceras_faltante_perfil").value);
        var piezas = perfil && Array.isArray(perfil.piezas) ? perfil.piezas : [];
        var pieza = piezas[Number(el("peceras_faltante_pieza").value)] || null;
        if (!perfil || !pieza) {
            return;
        }
        extras.push({
            id: "faltante_" + uuid(),
            tipo: "faltante",
            perfil_id: perfil.id,
            perfil_codigo: perfil.codigo || perfil.nombre,
            nombre: pieza.nombre || "Pieza faltante",
            largo: numero(pieza.largo || pieza.largo_cm),
            ancho: numero(pieza.ancho || pieza.ancho_cm),
            espesor: numero(pieza.espesor || pieza.espesor_mm || (perfil.datos || {}).espesor),
            cantidad: entero(el("peceras_faltante_cantidad").value)
        });
        render();
    }
    function renderDetalleAcomodo(hojas) {
        var contenedor = el("peceras_pedido_acomodo_detalle");
        var detalles = [];
        hojas.forEach(function (grupo) {
            if (!grupo.acomodo || !grupo.acomodo.hojas.length) {
                return;
            }
            var alternativas = Array.isArray(grupo.alternativasHoja) ? "<div class=\"text-muted small mb-2\">Alternativas 6 mm: " + grupo.alternativasHoja.map(function (opcion) { return escapeHtml(formato(opcion.largo, 0) + "x" + formato(opcion.ancho, 0) + " cm, " + opcion.hojas + " hoja(s), " + formato(opcion.desperdicio, 1) + "% desperdicio"); }).join(" | ") + "</div>" : "";
            detalles.push("<div class=\"border rounded p-3 mb-3\"><div class=\"fw-bold mb-2\">Acomodo " + escapeHtml(grupo.perfil_codigo ? grupo.perfil_codigo + " | " : "") + escapeHtml(formato(grupo.espesor, 2)) + " mm</div>" + alternativas +
                grupo.acomodo.hojas.map(function (hoja, index) {
                    var cortes = hoja.columnas ? hoja.columnas.length + " banda(s) con corte al ancho" : hoja.filas.length + " fila(s)";
                    var codigos = hoja.piezas.map(function (pieza) { return pieza.perfil_codigo || "Extra"; }).filter(function (codigo, posicion, lista) { return lista.indexOf(codigo) === posicion; }).join(", ");
                    return "<div class=\"text-muted small mb-1\">Hoja " + (index + 1) + ": " + cortes + ", " + hoja.piezas.length + " pieza(s), desperdicio " + formato(hoja.desperdicio, 1) + "%, códigos: " + escapeHtml(codigos) + "</div>";
                }).join("") + "</div>");
        });
        contenedor.innerHTML = detalles.join("");
        renderVisualAcomodo(hojas);
    }
    function perfilPorCodigo(codigo) {
        var buscado = String(codigo || "").toUpperCase();
        var item = items.find(function (actual) {
            return String(actual.perfil.codigo || actual.perfil.nombre || "").toUpperCase() === buscado;
        });
        if (item) {
            return item.perfil;
        }
        return perfiles.find(function (perfil) {
            return String(perfil.codigo || perfil.nombre || "").toUpperCase() === buscado;
        }) || null;
    }
    function metricasCajaHoja(hoja, hojaLargo, hojaAncho) {
        var derecha = 0;
        var abajo = 0;
        (hoja.piezas || []).forEach(function (pieza) {
            derecha = Math.max(derecha, numero(pieza.x) + numero(pieza.corteLargo || pieza.largo));
            abajo = Math.max(abajo, numero(pieza.y) + numero(pieza.ancho));
        });
        return {
            derecha: Math.min(hojaLargo, derecha),
            abajo: Math.min(hojaAncho, abajo),
            libreLargo: Math.max(0, hojaLargo - derecha),
            libreAncho: Math.max(0, hojaAncho - abajo)
        };
    }
    function juegosCompletosHoja(hoja, perfil) {
        if (!perfil || !Array.isArray(perfil.piezas) || !perfil.piezas.length) {
            return 0;
        }
        var conteo = {};
        (hoja.piezas || []).forEach(function (pieza) {
            conteo[pieza.pieza] = (conteo[pieza.pieza] || 0) + 1;
        });
        var completos = Infinity;
        perfil.piezas.forEach(function (pieza) {
            var nombre = pieza.nombre || "Pieza";
            var requerido = entero(pieza.cantidad || pieza.cantidad_por_pecera || 1);
            completos = Math.min(completos, Math.floor((conteo[nombre] || 0) / requerido));
        });
        return completos === Infinity ? 0 : completos;
    }
    function juegosAdicionalesHoja(hoja, grupo) {
        var perfil = perfilPorCodigo(grupo.perfil_codigo);
        if (!perfil || !Array.isArray(perfil.piezas) || !perfil.piezas.length) {
            return 0;
        }
        var simulada = JSON.parse(JSON.stringify(hoja));
        var piezasReceta = perfil.piezas.slice().sort(function (a, b) {
            return (numero(b.largo) * numero(b.ancho)) - (numero(a.largo) * numero(a.ancho));
        });
        var adicionales = 0;
        for (var juego = 0; juego < 20; juego++) {
            var candidata = JSON.parse(JSON.stringify(simulada));
            var cabe = true;
            for (var i = 0; i < piezasReceta.length && cabe; i++) {
                var pieza = piezasReceta[i];
                var cantidad = entero(pieza.cantidad || pieza.cantidad_por_pecera || 1);
                for (var j = 0; j < cantidad; j++) {
                    var unidad = {
                        perfil_codigo: perfil.codigo || perfil.nombre,
                        perfil_nombre: perfil.nombre || perfil.codigo,
                        pieza: pieza.nombre || "Pieza",
                        largo: numero(pieza.largo || pieza.largo_cm),
                        ancho: numero(pieza.ancho || pieza.ancho_cm),
                        espesor: numero((perfil.datos || {}).espesor || pieza.espesor || pieza.espesor_mm)
                    };
                    cabe = grupo.tipoAcomodo === "filas_rotacion"
                        ? colocarEnHoja(candidata, unidad, numero(grupo.hojaLargo), numero(grupo.hojaAncho), numero(grupo.separacionCm))
                        : colocarEnHojaColumnas(candidata, unidad, numero(grupo.hojaLargo), numero(grupo.hojaAncho), numero(grupo.separacionCm));
                    if (!cabe) { break; }
                }
            }
            if (!cabe) { break; }
            simulada = candidata;
            adicionales++;
        }
        return adicionales;
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-31
     * Proposito: proponer piezas completas de perfiles vigentes que puedan aprovechar un sobrante.
     * Impacto: Produccion/Pedido multiple vidrio; evita pedir una hoja adicional cuando existe una pieza util compatible.
     * Contrato: solo recomienda piezas que el mismo algoritmo logra colocar en la hoja; no las agrega sin confirmacion.
     */
    function piezasSugeridasEnSobrante(hoja, grupo) {
        var candidatos = [];
        var vistos = {};
        var fuente = perfiles.slice();
        if (grupo.perfil_codigo) {
            var actual = perfilPorCodigo(grupo.perfil_codigo);
            if (actual) { fuente.push(actual); }
        }
        fuente.forEach(function (perfil) {
            var espesor = numero((perfil.datos || {}).espesor || perfil.espesor_mm);
            if (Math.abs(espesor - numero(grupo.espesor)) > 0.01 || !Array.isArray(perfil.piezas)) {
                return;
            }
            perfil.piezas.forEach(function (pieza) {
                var largo = numero(pieza.largo || pieza.largo_cm);
                var ancho = numero(pieza.ancho || pieza.ancho_cm);
                var clave = (perfil.codigo || perfil.nombre || "Perfil") + "|" + pieza.nombre + "|" + largo + "|" + ancho;
                if (largo <= 0 || ancho <= 0 || vistos[clave]) { return; }
                vistos[clave] = true;
                var candidata = {
                    perfil_codigo: perfil.codigo || perfil.nombre || "Perfil",
                    pieza: pieza.nombre || "Pieza",
                    largo: largo,
                    ancho: ancho,
                    espesor: espesor
                };
                var simulada = JSON.parse(JSON.stringify(hoja));
                var cabe = grupo.tipoAcomodo === "filas_rotacion"
                    ? colocarEnHoja(simulada, candidata, numero(grupo.hojaLargo), numero(grupo.hojaAncho), numero(grupo.separacionCm))
                    : colocarEnHojaColumnas(simulada, candidata, numero(grupo.hojaLargo), numero(grupo.hojaAncho), numero(grupo.separacionCm));
                if (cabe) {
                    candidatos.push({perfil: candidata.perfil_codigo, pieza: candidata.pieza, largo: largo, ancho: ancho, area: largo * ancho});
                }
            });
        });
        return candidatos.sort(function (a, b) { return b.area - a.area; }).slice(0, 3);
    }
    function recomendacionSobrante(hoja, grupo, adicionales) {
        var caja = metricasCajaHoja(hoja, numero(grupo.hojaLargo), numero(grupo.hojaAncho));
        var minimo = Math.min(caja.libreLargo, caja.libreAncho);
        if (adicionales > 0) {
            return "Se recomienda usar primero los " + adicionales + " juego(s) adicional(es) detectados.";
        }
        if (minimo < 5) {
            return "Sobrante menor a 5 cm en uno de sus lados: no es práctico pedir una pieza adicional; conviene dejarlo como desperdicio de corte.";
        }
        var piezas = piezasSugeridasEnSobrante(hoja, grupo);
        if (piezas.length) {
            return "Piezas completas viables para aprovecharlo: " + piezas.map(function (pieza) {
                return pieza.perfil + " / " + pieza.pieza + " (" + formato(pieza.largo, 1) + " x " + formato(pieza.ancho, 1) + " cm)";
            }).join(", ") + ".";
        }
        return "No se encontró una pieza completa de los perfiles actuales que aproveche este espacio; conviene conservarlo como sobrante útil para reparaciones, plataformas o rampas.";
    }
    function agregarJuegosPropuestos(codigo, cantidad) {
        var buscado = String(codigo || "").toUpperCase();
        var item = items.find(function (actual) {
            return String(actual.perfil.codigo || actual.perfil.nombre || "").toUpperCase() === buscado;
        });
        if (!item || cantidad <= 0) { return; }
        item.cantidad += cantidad;
        render();
    }
    function resumenHojaVisual(hoja, grupo) {
        var caja = metricasCajaHoja(hoja, numero(grupo.hojaLargo), numero(grupo.hojaAncho));
        var perfil = perfilPorCodigo(grupo.perfil_codigo);
        var juegos = juegosCompletosHoja(hoja, perfil);
        var adicionales = juegosAdicionalesHoja(hoja, grupo);
        var ocupacion = numero(hoja.aprovechamiento);
        var piezas = (hoja.piezas || []).length;
        var propuesta = adicionales > 0
            ? "Propuesta: agregar " + adicionales + " juego(s) adicional(es) de " + (grupo.perfil_codigo || "este perfil") + " para aprovechar el espacio disponible. <button type=\"button\" class=\"btn btn-sm btn-primary ms-2\" data-agregar-juegos=\"" + escapeHtml(grupo.perfil_codigo || "") + "\" data-cantidad-juegos=\"" + adicionales + "\">Agregar al pedido</button>"
            : "Propuesta: no se detecta otro juego completo en el espacio libre actual.";
        if (grupo.hojasSolicitadas > 0 && grupo.hojas > grupo.hojasSolicitadas) {
            propuesta += " Atención: el pedido requiere " + grupo.hojas + " hojas y solo se indicaron " + grupo.hojasSolicitadas + "; esta hoja queda fuera de la cantidad señalada.";
        }
        return "<div class=\"row g-2 small text-muted mb-2\">" +
            "<div class=\"col-md-3\"><strong>Uso:</strong> " + escapeHtml(formato(ocupacion, 1)) + "% (" + escapeHtml(formato(hoja.areaUsada, 4)) + " de " + escapeHtml(formato(hoja.areaHoja, 4)) + " m2)</div>" +
            "<div class=\"col-md-3\"><strong>Envolvente:</strong> " + escapeHtml(formato(caja.derecha, 1)) + " x " + escapeHtml(formato(caja.abajo, 1)) + " cm</div>" +
            "<div class=\"col-md-3\"><strong>Libre:</strong> " + escapeHtml(formato(caja.libreLargo, 1)) + " x " + escapeHtml(formato(caja.libreAncho, 1)) + " cm</div>" +
            "<div class=\"col-md-3\"><strong>Piezas/juegos:</strong> " + escapeHtml(piezas) + " piezas; " + escapeHtml(juegos) + " juego(s) completos</div>" +
            "</div><div class=\"alert alert-light-primary py-2 px-3 mb-2\">" + propuesta + "</div><div class=\"text-muted small mb-2\"><strong>Sobrante:</strong> " + escapeHtml(recomendacionSobrante(hoja, grupo, adicionales)) + "</div>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: pintar una vista proporcional del acomodo por hoja para revision operativa.
     * Impacto: Produccion/Pedido multiple vidrio; ayuda a detectar cortes grandes y aprovechamiento visual.
     * Contrato: visualiza la heuristica de filas; no sustituye plano CAD/CAM final del proveedor.
     */
    function renderVisualAcomodo(hojas) {
        var contenedor = el("peceras_pedido_acomodo_visual");
        if (el("peceras_pedido_acomodo").value === "area") {
            contenedor.innerHTML = "<div class=\"text-muted\">Cambia el tipo de acomodo a bandas corte ancho o filas con rotacion para ver hojas visuales.</div>";
            return;
        }
        var bloques = [];
        hojas.forEach(function (grupo) {
            if (!grupo.acomodo || !grupo.acomodo.hojas.length) {
                return;
            }
            var hojaLargo = numero(grupo.hojaLargo);
            var hojaAncho = numero(grupo.hojaAncho);
            grupo.acomodo.hojas.forEach(function (hoja, index) {
                var altoVisual = Math.max(220, Math.min(420, (hojaAncho / Math.max(hojaLargo, 1)) * 520));
                var caja = metricasCajaHoja(hoja, hojaLargo, hojaAncho);
                var reglaSuperior = "<div class=\"vidrio-ruler-top\">Largo de hoja: " + escapeHtml(formato(hojaLargo, 1)) + " cm | Usado: " + escapeHtml(formato(caja.derecha, 1)) + " cm | Libre: " + escapeHtml(formato(caja.libreLargo, 1)) + " cm</div>";
                var reglaLateral = "<div class=\"vidrio-ruler-side\">Ancho usado:<br>" + escapeHtml(formato(caja.abajo, 1)) + " / " + escapeHtml(formato(hojaAncho, 1)) + " cm<br>Libre: " + escapeHtml(formato(caja.libreAncho, 1)) + " cm</div>";
                var bandasHtml = hoja.columnas ? hoja.columnas.map(function (columna) {
                    return "<div class=\"vidrio-band\" style=\"left:" + ((columna.x / hojaLargo) * 100) + "%;width:" + ((columna.ancho / hojaLargo) * 100) + "%\"></div>";
                }).join("") : "";
                var piezasHtml = hoja.piezas.map(function (pieza) {
                    var left = (pieza.x / hojaLargo) * 100;
                    var top = (pieza.y / hojaAncho) * 100;
                    var width = ((pieza.corteLargo || pieza.largo) / hojaLargo) * 100;
                    var height = (pieza.ancho / hojaAncho) * 100;
                    var label = (pieza.perfil_codigo || "Extra") + " / " + pieza.pieza + " " + formato(pieza.largo, 1) + "x" + formato(pieza.ancho, 1) + (pieza.corteLargo && pieza.corteLargo !== pieza.largo ? " corte " + formato(pieza.corteLargo, 1) : "");
                    return "<div class=\"vidrio-piece\" style=\"left:" + left + "%;top:" + top + "%;width:" + width + "%;height:" + height + "%\" title=\"" + escapeHtml(label) + "\">" + escapeHtml(label) + "</div>";
                }).join("");
                var estadoHoja = grupo.hojasSolicitadas > 0 && index + 1 > grupo.hojasSolicitadas
                    ? "<div class=\"alert alert-light-danger py-2 px-3 mb-2\">Hoja adicional no contemplada: las piezas no caben en las " + escapeHtml(grupo.hojasSolicitadas) + " hojas indicadas.</div>"
                    : "";
                bloques.push("<div><div class=\"d-flex flex-wrap align-items-center gap-2 mb-2\"><div class=\"fw-bold\">" + escapeHtml(grupo.perfil_codigo ? grupo.perfil_codigo + " | " : "") + escapeHtml(formato(grupo.espesor, 2)) + " mm - Hoja " + (index + 1) + "</div><div class=\"vidrio-waste small\">Desperdicio " + escapeHtml(formato(hoja.desperdicio, 1)) + "% | Aprovechado " + escapeHtml(formato(hoja.aprovechamiento, 1)) + "%</div></div>" + resumenHojaVisual(hoja, grupo) + estadoHoja + reglaSuperior + "<div class=\"vidrio-sheet-wrap\">" + reglaLateral + "<div class=\"flex-grow-1\"><div class=\"vidrio-sheet\" style=\"height:" + altoVisual + "px\">" + bandasHtml + piezasHtml + "</div></div></div></div>");
            });
        });
        contenedor.innerHTML = bloques.join("") || "<div class=\"text-muted\">Sin hojas acomodadas para mostrar.</div>";
    }
    function snapshotPedido() {
        var hojas = resumenHojas();
        return {
            id_pedido_vidrio: 0,
            nombre: el("peceras_pedido_nombre").value.trim() || "Pedido vidrio " + new Date().toLocaleDateString("es-MX"),
            fecha: new Date().toISOString(),
            tipo_acomodo: el("peceras_pedido_acomodo").value,
            desperdicio_promedio: desperdicioPromedioHojas(hojas),
            hojas_total: hojas.reduce(function (total, grupo) { return total + grupo.hojas; }, 0),
            piezas_total: piezasPedido().reduce(function (total, pieza) { return total + pieza.cantidad; }, 0),
            area_total_m2: piezasPedido().reduce(function (total, pieza) { return total + pieza.area; }, 0),
            configs_hoja: configsHoja,
            bloques_proveedor: planProveedor,
            recomendaciones_hoja: hojas.map(function (grupo) {
                return {espesor: grupo.espesor, largo: grupo.hojaLargo, ancho: grupo.hojaAncho, hojas: grupo.hojas, alternativas: grupo.alternativasHoja || []};
            }),
            extras: extras,
            items: items.map(function (item) {
                return {cantidad: item.cantidad, perfil: item.perfil};
            })
        };
    }
    function guardarPedidoActual() {
        var pedido = snapshotPedido();
        var body = new URLSearchParams();
        body.append("pedido", JSON.stringify(pedido));
        fetch(endpoints.guardarPedido, {method: "POST", credentials: "same-origin", headers: {"Accept": "application/json", "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""}, body: body.toString()}).then(jsonResponse).then(function (respuesta) {
            if (respuesta.error) { throw new Error(respuesta.mensaje || "No fue posible guardar el pedido."); }
            return cargarPedidosGuardados();
        }).then(renderPedidosGuardados).catch(function (error) { alert(error.message); });
    }
    function cargarPedidoGuardado(id) {
        var pedido = pedidosGuardados.find(function (item) { return String(item.id_pedido_vidrio) === String(id); });
        if (!pedido) {
            return;
        }
        items = Array.isArray(pedido.items) ? pedido.items.map(function (item) {
            return {id: uuid(), cantidad: entero(item.cantidad), perfil: item.perfil};
        }) : [];
        extras = Array.isArray(pedido.extras) ? pedido.extras : [];
        configsHoja = pedido.configs_hoja || {};
        planProveedor = Array.isArray(pedido.bloques_proveedor) ? pedido.bloques_proveedor : [];
        el("peceras_pedido_acomodo").value = pedido.tipo_acomodo || "columnas_ancho";
        el("peceras_pedido_nombre").value = pedido.nombre || "";
        renderReportePedido(pedido);
        render();
    }
    function renderReportePedido(pedido) {
        var contenedor = el("peceras_pedido_reporte");
        if (!contenedor) { return; }
        var plan = Array.isArray(pedido.recomendaciones_hoja) ? pedido.recomendaciones_hoja : [];
        var pendientes = Array.isArray(pedido.pendientes) ? pedido.pendientes : [];
        var bloques = Array.isArray(pedido.bloques_proveedor) ? pedido.bloques_proveedor : [];
        var estrategia = pedido.estrategia_sobrantes || {};
        if (!plan.length && !pendientes.length && !bloques.length) {
            contenedor.innerHTML = "";
            return;
        }
        var filas = plan.map(function (grupo) {
            return "<tr><td>" + escapeHtml(grupo.grupo || "") + "</td><td>" + escapeHtml(grupo.descripcion || "") + "</td><td>" + escapeHtml(grupo.hoja || "") + "</td><td>" + escapeHtml(grupo.resultado || "") + "</td></tr>";
        }).join("");
        var bloquesHtml = bloques.length ? "<div class=\"mt-4\"><div class=\"fw-bold mb-2\">Plan por bloques del proveedor</div><div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Bloque</th><th>Códigos</th><th>Hoja</th><th>Hojas</th><th>Objetivo</th></tr></thead><tbody>" + bloques.map(function (bloque) {
            return "<tr><td><span class=\"badge badge-light-primary\">" + escapeHtml(bloque.id || "") + "</span></td><td>" + escapeHtml((bloque.perfiles || []).join(", ")) + "</td><td>" + escapeHtml(bloque.hoja || "") + "</td><td>" + escapeHtml(bloque.hojas_reservadas || "") + "</td><td>" + escapeHtml(bloque.objetivo || "") + "</td></tr>";
        }).join("") + "</tbody></table></div></div>" : "";
        var estrategiaHtml = estrategia.modo ? "<div class=\"alert alert-info mt-4 mb-0\"><div class=\"fw-bold mb-1\">Sobrantes</div><div>" + escapeHtml(estrategia.nota || "El sobrante se decide por hoja.") + "</div></div>" : "";
        var pendientesHtml = pendientes.length ? "<div class=\"alert alert-warning mt-4 mb-0\"><div class=\"fw-bold mb-2\">Pendientes antes de confirmar</div><ul class=\"mb-0\">" + pendientes.map(function (pendiente) { return "<li>" + escapeHtml(pendiente) + "</li>"; }).join("") + "</ul></div>" : "";
        contenedor.innerHTML = "<div class=\"border rounded p-4\"><div class=\"fw-bold fs-4 mb-3\">Reporte de simulación</div>" + (filas ? "<div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Grupo</th><th>Combinación</th><th>Hoja</th><th>Resultado</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "") + bloquesHtml + estrategiaHtml + pendientesHtml + "</div>";
    }
    function renderPedidosGuardados() {
        var contenedor = el("peceras_pedido_guardados");
        if (!contenedor) {
            return;
        }
        if (!pedidosGuardados.length) {
            contenedor.innerHTML = "<div class=\"text-muted\">Sin pedidos guardados en el sistema.</div>";
            return;
        }
        var ordenados = pedidosGuardados.slice().sort(function (a, b) {
            return numero(a.desperdicio_promedio) - numero(b.desperdicio_promedio);
        });
        contenedor.innerHTML = "<div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle gy-3\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Pedido</th><th>Desperdicio</th><th>Hojas</th><th>Piezas</th><th>Fecha</th><th class=\"text-end\"></th></tr></thead><tbody>" +
            ordenados.map(function (pedido, index) {
                var esBorrador = /simulaci[oó]n/i.test(pedido.nombre || "");
                var etiqueta = esBorrador ? "<span class=\"badge badge-light-warning me-2\">Borrador</span>" : (index === 0 ? "<span class=\"badge badge-light-success me-2\">Mejor</span>" : "");
                return "<tr><td>" + etiqueta + escapeHtml(pedido.nombre) + "</td><td>" + escapeHtml(formato(pedido.desperdicio_promedio, 1)) + "%</td><td>" + escapeHtml(pedido.hojas_total) + "</td><td>" + escapeHtml(pedido.piezas_total) + "</td><td>" + escapeHtml(new Date(pedido.fecha).toLocaleString("es-MX")) + "</td><td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pedido-cargar=\"" + escapeHtml(pedido.id_pedido_vidrio) + "\">Cargar</button></td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function agregarPerfil(id, cantidad) {
        var perfil = perfilPorId(id);
        if (!perfil) {
            return;
        }
        items.push({id: uuid(), perfil: perfil, cantidad: entero(cantidad)});
        render();
    }
    function textoPedido() {
        var hojas = resumenHojas();
        var lineas = ["PEDIDO MULTIPLE DE VIDRIO", ""];
        items.forEach(function (item, index) {
            lineas.push((index + 1) + ". " + (item.perfil.nombre || "Pecera sin nombre") + " - cantidad perfiles: " + item.cantidad);
        });
        lineas.push("");
        lineas.push("Hojas estimadas:");
        hojas.forEach(function (grupo) {
            lineas.push("- " + formato(grupo.espesor, 2) + " mm: " + grupo.hojas + " hoja(s), " + grupo.descripcionAcomodo);
        });
        lineas.push("");
        lineas.push("Detalle de piezas:");
        piezasPedido().forEach(function (pieza) {
            lineas.push("- " + pieza.perfil_nombre + " / " + pieza.pieza + ": " + formato(pieza.largo, 2) + " x " + formato(pieza.ancho, 2) + " cm, " + formato(pieza.espesor, 2) + " mm, total " + pieza.cantidad);
        });
        return lineas.join("\n");
    }
    function exportarCsv() {
        var filas = [["perfil", "pieza", "largo_cm", "ancho_cm", "espesor_mm", "cantidad_total", "area_m2"]];
        piezasPedido().forEach(function (pieza) {
            filas.push([pieza.perfil_nombre, pieza.pieza, formato(pieza.largo, 2), formato(pieza.ancho, 2), formato(pieza.espesor, 2), pieza.cantidad, formato(pieza.area, 6)]);
        });
        descargarArchivo("pedido-multiple-vidrio.csv", filas.map(function (fila) { return fila.map(csvValue).join(","); }).join("\r\n"));
    }
    function init() {
        Promise.all([cargarPerfiles(), cargarPedidosGuardados()]).then(function () {
            cargarCatalogoFaltantes();
            var params = new URLSearchParams(window.location.search);
            if (params.get("perfil")) {
                agregarPerfil(params.get("perfil"), 1);
            }
            render();
        }).catch(function (error) { alert(error.message); render(); });
        el("peceras_pedido_agregar").addEventListener("click", function () {
            agregarPerfil(el("peceras_pedido_perfil").value, el("peceras_pedido_cantidad").value);
        });
        el("peceras_extra_agregar").addEventListener("click", function () {
            agregarExtra({
                nombre: el("peceras_extra_nombre").value.trim(),
                largo: el("peceras_extra_largo").value,
                ancho: el("peceras_extra_ancho").value,
                espesor: el("peceras_extra_espesor").value,
                cantidad: el("peceras_extra_cantidad").value
            });
        });
        el("peceras_faltante_perfil").addEventListener("change", function () {
            el("peceras_faltante_cantidad").value = 1;
            cargarPiezasFaltantes();
        });
        el("peceras_faltante_pieza").addEventListener("change", mostrarMedidaFaltante);
        el("peceras_faltante_agregar").addEventListener("click", agregarFaltante);
        el("peceras_pedido_guardar").addEventListener("click", guardarPedidoActual);
        ["peceras_pedido_hoja_largo", "peceras_pedido_hoja_ancho", "peceras_pedido_merma", "peceras_pedido_separacion"].forEach(function (id) {
            el(id).addEventListener("input", function () { render(); });
            el(id).addEventListener("change", function () { render(); });
        });
        el("peceras_pedido_acomodo").addEventListener("change", function () { render(); });
        el("peceras_pedido_tipos_hoja").addEventListener("input", function (event) {
            var input = event.target.closest("[data-hoja-campo]");
            var fila = input ? input.closest("tr[data-espesor]") : null;
            if (!input || !fila) {
                return;
            }
            var key = fila.getAttribute("data-espesor");
            configsHoja[key] = configsHoja[key] || configDefaultHoja(Number(key));
            configsHoja[key][input.getAttribute("data-hoja-campo")] = numero(input.value);
            render(true);
        });
        document.addEventListener("click", function (event) {
            var agregarJuegos = event.target.closest("[data-agregar-juegos]");
            if (agregarJuegos) {
                agregarJuegosPropuestos(agregarJuegos.getAttribute("data-agregar-juegos"), entero(agregarJuegos.getAttribute("data-cantidad-juegos")));
                return;
            }
            var quitar = event.target.closest("[data-quitar-item]");
            if (!quitar) {
                var extraQuitar = event.target.closest("[data-extra-quitar]");
                if (extraQuitar) {
                    extras = extras.filter(function (pieza) { return pieza.id !== extraQuitar.getAttribute("data-extra-quitar"); });
                    render();
                    return;
                }
                var extraCopiar = event.target.closest("[data-extra-copiar]");
                if (extraCopiar) {
                    var partes = String(extraCopiar.getAttribute("data-extra-copiar") || "").split("|");
                    agregarExtra({nombre: partes[1] || "Pieza extra", largo: partes[2], ancho: partes[3], espesor: partes[4], cantidad: 1});
                }
                var pedidoCargar = event.target.closest("[data-pedido-cargar]");
                if (pedidoCargar) {
                    cargarPedidoGuardado(pedidoCargar.getAttribute("data-pedido-cargar"));
                    return;
                }
                return;
            }
            items = items.filter(function (item) { return item.id !== quitar.getAttribute("data-quitar-item"); });
            render();
        });
        el("peceras_pedido_copiar").addEventListener("click", function () {
            var texto = textoPedido();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto);
            } else {
                window.prompt("Copia el pedido:", texto);
            }
        });
        el("peceras_pedido_csv").addEventListener("click", exportarCsv);
    }
    document.addEventListener("DOMContentLoaded", init);
})();
