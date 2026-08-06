"use strict";
(function () {
    var storageKey = "erp_produccion_peceras_borradores_v1";
    var pedidosStorageKey = "erp_produccion_peceras_pedidos_vidrio_v1";
    var perfiles = [];
    var items = [];
    var configsHoja = {};
    var extras = [];
    var pedidosGuardados = [];

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
        try {
            perfiles = JSON.parse(localStorage.getItem(storageKey) || "[]");
        } catch (e) {
            perfiles = [];
        }
        el("peceras_pedido_perfil").innerHTML = perfiles.length
            ? perfiles.map(function (perfil) { return "<option value=\"" + escapeHtml(perfil.id) + "\">" + escapeHtml(perfil.nombre || "Pecera sin nombre") + "</option>"; }).join("")
            : "<option value=\"\">Sin perfiles guardados</option>";
    }
    function cargarPedidosGuardados() {
        try {
            pedidosGuardados = JSON.parse(localStorage.getItem(pedidosStorageKey) || "[]");
        } catch (e) {
            pedidosGuardados = [];
        }
    }
    function persistirPedidosGuardados() {
        localStorage.setItem(pedidosStorageKey, JSON.stringify(pedidosGuardados.slice(0, 40)));
    }
    function perfilPorId(id) {
        return perfiles.find(function (perfil) { return String(perfil.id) === String(id); });
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
            var piezas = Array.isArray(perfil.piezas) ? perfil.piezas : [];
            piezas.forEach(function (pieza) {
                salida.push({
                    perfil_id: perfil.id,
                    perfil_nombre: perfil.nombre || "Pecera sin nombre",
                    pieza: pieza.nombre || "Pieza",
                    largo: numero(pieza.largo || pieza.largo_cm),
                    ancho: numero(pieza.ancho || pieza.ancho_cm),
                    espesor: numero(pieza.espesor || pieza.espesor_mm || d.espesor),
                    cantidad: entero(pieza.cantidad || pieza.cantidad_por_pecera || 1) * item.cantidad,
                    area: areaPieza(pieza, item.cantidad)
                });
            });
        });
        extras.forEach(function (pieza) {
            salida.push({
                perfil_id: "extra",
                perfil_nombre: "Extra",
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
    function configDefaultHoja() {
        return {
            largo: numero(el("peceras_pedido_hoja_largo").value),
            ancho: numero(el("peceras_pedido_hoja_ancho").value),
            merma: Math.max(0, numero(el("peceras_pedido_merma").value)),
            separacion: Math.max(0, numero(el("peceras_pedido_separacion").value))
        };
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-04
     * Proposito: detectar tipos de hoja por espesor y permitir medidas distintas por cada grupo.
     * Impacto: Produccion/Pedido multiple vidrio; evita asumir una sola hoja cuando el pedido mezcla espesores.
     * Contrato: la deteccion usa el espesor de cada pieza; las medidas se capturan localmente en la vista.
     */
    function sincronizarTiposHoja(piezas) {
        var defaults = configDefaultHoja();
        var espesores = {};
        piezas.forEach(function (pieza) {
            espesores[formato(pieza.espesor, 2)] = true;
        });
        Object.keys(espesores).forEach(function (key) {
            if (!configsHoja[key]) {
                configsHoja[key] = Object.assign({}, defaults);
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
            return "<tr data-espesor=\"" + escapeHtml(key) + "\">" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(key) + " mm</span></td>" +
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
            var key = formato(pieza.espesor, 2);
            if (!grupos[key]) {
                grupos[key] = {espesor: pieza.espesor, piezas: 0, area: 0, alertas: [], piezasDetalle: []};
            }
            grupos[key].piezas += pieza.cantidad;
            grupos[key].area += pieza.area;
            grupos[key].piezasDetalle.push(pieza);
        });
        return Object.keys(grupos).sort().map(function (key) {
            var g = grupos[key];
            var config = configsHoja[key] || configDefaultHoja();
            var hojaLargo = numero(config.largo);
            var hojaAncho = numero(config.ancho);
            var merma = Math.max(0, numero(config.merma)) / 100;
            var separacionCm = Math.max(0, numero(config.separacion)) / 10;
            var areaHoja = (hojaLargo / 100) * (hojaAncho / 100);
            g.hojaLargo = hojaLargo;
            g.hojaAncho = hojaAncho;
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
            var colocada = false;
            for (var h = 0; h < hojas.length && !colocada; h++) {
                colocada = colocarEnHojaColumnas(hojas[h], pieza, hojaLargo, hojaAncho, separacionCm);
            }
            if (!colocada) {
                var hoja = {columnas: [], filas: [], piezas: []};
                hojas.push(hoja);
                if (!colocarEnHojaColumnas(hoja, pieza, hojaLargo, hojaAncho, separacionCm)) {
                    alertas.push(pieza.pieza + " no cabe en hoja " + formato(hojaLargo, 0) + "x" + formato(hojaAncho, 0));
                }
            }
        });
        return {hojas: hojas, alertas: alertas};
    }
    function colocarEnHojaColumnas(hoja, pieza, hojaLargo, hojaAncho, separacionCm) {
        var opciones = orientacionesPieza(pieza, hojaLargo, hojaAncho).sort(function (a, b) {
            return a.largo - b.largo || b.ancho - a.ancho;
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
            var colocada = false;
            for (var h = 0; h < hojas.length && !colocada; h++) {
                colocada = colocarEnHoja(hojas[h], pieza, hojaLargo, hojaAncho, separacionCm);
            }
            if (!colocada) {
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
            return "<tr><td>" + escapeHtml(formato(grupo.espesor, 2)) + " mm</td><td>" + escapeHtml(grupo.piezas) + "</td><td>" + escapeHtml(formato(grupo.area, 4)) + " m2</td><td>" + escapeHtml(grupo.descripcionAcomodo) + "</td><td><span class=\"badge badge-light-primary fs-7\">" + escapeHtml(grupo.hojas) + "</span></td><td>" + escapeHtml(grupo.alertas.slice(0, 3).join(", ")) + "</td></tr>";
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
            return "<div class=\"d-flex align-items-center justify-content-between border rounded p-3 mb-2\">" +
                "<div><span class=\"fw-bold\">" + escapeHtml(pieza.nombre) + "</span><span class=\"text-muted ms-2\">" + escapeHtml(formato(pieza.largo, 2)) + " x " + escapeHtml(formato(pieza.ancho, 2)) + " cm, " + escapeHtml(formato(pieza.espesor, 2)) + " mm, " + escapeHtml(pieza.cantidad) + " pza</span></div>" +
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
    function renderDetalleAcomodo(hojas) {
        var contenedor = el("peceras_pedido_acomodo_detalle");
        var detalles = [];
        hojas.forEach(function (grupo) {
            if (!grupo.acomodo || !grupo.acomodo.hojas.length) {
                return;
            }
            detalles.push("<div class=\"border rounded p-3 mb-3\"><div class=\"fw-bold mb-2\">Acomodo " + escapeHtml(formato(grupo.espesor, 2)) + " mm</div>" +
                grupo.acomodo.hojas.map(function (hoja, index) {
                    var cortes = hoja.columnas ? hoja.columnas.length + " banda(s) con corte al ancho" : hoja.filas.length + " fila(s)";
                    return "<div class=\"text-muted small mb-1\">Hoja " + (index + 1) + ": " + cortes + ", " + hoja.piezas.length + " pieza(s), desperdicio " + formato(hoja.desperdicio, 1) + "%</div>";
                }).join("") + "</div>");
        });
        contenedor.innerHTML = detalles.join("");
        renderVisualAcomodo(hojas);
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
                var bandasHtml = hoja.columnas ? hoja.columnas.map(function (columna) {
                    return "<div class=\"vidrio-band\" style=\"left:" + ((columna.x / hojaLargo) * 100) + "%;width:" + ((columna.ancho / hojaLargo) * 100) + "%\"></div>";
                }).join("") : "";
                var piezasHtml = hoja.piezas.map(function (pieza) {
                    var left = (pieza.x / hojaLargo) * 100;
                    var top = (pieza.y / hojaAncho) * 100;
                    var width = ((pieza.corteLargo || pieza.largo) / hojaLargo) * 100;
                    var height = (pieza.ancho / hojaAncho) * 100;
                    var label = pieza.pieza + " " + formato(pieza.largo, 1) + "x" + formato(pieza.ancho, 1) + (pieza.corteLargo && pieza.corteLargo !== pieza.largo ? " corte " + formato(pieza.corteLargo, 1) : "");
                    return "<div class=\"vidrio-piece\" style=\"left:" + left + "%;top:" + top + "%;width:" + width + "%;height:" + height + "%\" title=\"" + escapeHtml(label) + "\">" + escapeHtml(label) + "</div>";
                }).join("");
                bloques.push("<div><div class=\"d-flex flex-wrap align-items-center gap-2 mb-2\"><div class=\"fw-bold\">" + escapeHtml(formato(grupo.espesor, 2)) + " mm - Hoja " + (index + 1) + "</div><div class=\"vidrio-waste small\">Desperdicio " + escapeHtml(formato(hoja.desperdicio, 1)) + "% | Aprovechado " + escapeHtml(formato(hoja.aprovechamiento, 1)) + "%</div></div><div class=\"vidrio-sheet\" style=\"height:" + altoVisual + "px\">" + bandasHtml + piezasHtml + "</div></div>");
            });
        });
        contenedor.innerHTML = bloques.join("") || "<div class=\"text-muted\">Sin hojas acomodadas para mostrar.</div>";
    }
    function snapshotPedido() {
        var hojas = resumenHojas();
        return {
            id: "pedido_" + uuid(),
            nombre: el("peceras_pedido_nombre").value.trim() || "Pedido vidrio " + new Date().toLocaleDateString("es-MX"),
            fecha: new Date().toISOString(),
            tipo_acomodo: el("peceras_pedido_acomodo").value,
            desperdicio_promedio: desperdicioPromedioHojas(hojas),
            hojas_total: hojas.reduce(function (total, grupo) { return total + grupo.hojas; }, 0),
            piezas_total: piezasPedido().reduce(function (total, pieza) { return total + pieza.cantidad; }, 0),
            configs_hoja: configsHoja,
            extras: extras,
            items: items.map(function (item) {
                return {cantidad: item.cantidad, perfil: item.perfil};
            })
        };
    }
    function guardarPedidoActual() {
        var pedido = snapshotPedido();
        pedidosGuardados = pedidosGuardados.filter(function (item) { return item.id !== pedido.id; });
        pedidosGuardados.unshift(pedido);
        persistirPedidosGuardados();
        renderPedidosGuardados();
    }
    function cargarPedidoGuardado(id) {
        var pedido = pedidosGuardados.find(function (item) { return item.id === id; });
        if (!pedido) {
            return;
        }
        items = Array.isArray(pedido.items) ? pedido.items.map(function (item) {
            return {id: uuid(), cantidad: entero(item.cantidad), perfil: item.perfil};
        }) : [];
        extras = Array.isArray(pedido.extras) ? pedido.extras : [];
        configsHoja = pedido.configs_hoja || {};
        el("peceras_pedido_acomodo").value = pedido.tipo_acomodo || "columnas_ancho";
        el("peceras_pedido_nombre").value = pedido.nombre || "";
        render();
    }
    function renderPedidosGuardados() {
        var contenedor = el("peceras_pedido_guardados");
        if (!contenedor) {
            return;
        }
        if (!pedidosGuardados.length) {
            contenedor.innerHTML = "<div class=\"text-muted\">Sin pedidos guardados en este navegador.</div>";
            return;
        }
        var ordenados = pedidosGuardados.slice().sort(function (a, b) {
            return numero(a.desperdicio_promedio) - numero(b.desperdicio_promedio);
        });
        contenedor.innerHTML = "<div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle gy-3\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Pedido</th><th>Desperdicio</th><th>Hojas</th><th>Piezas</th><th>Fecha</th><th class=\"text-end\"></th></tr></thead><tbody>" +
            ordenados.map(function (pedido, index) {
                return "<tr><td>" + (index === 0 ? "<span class=\"badge badge-light-success me-2\">Mejor</span>" : "") + escapeHtml(pedido.nombre) + "</td><td>" + escapeHtml(formato(pedido.desperdicio_promedio, 1)) + "%</td><td>" + escapeHtml(pedido.hojas_total) + "</td><td>" + escapeHtml(pedido.piezas_total) + "</td><td>" + escapeHtml(new Date(pedido.fecha).toLocaleString("es-MX")) + "</td><td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pedido-cargar=\"" + escapeHtml(pedido.id) + "\">Cargar</button><button class=\"btn btn-sm btn-light-danger ms-2\" type=\"button\" data-pedido-eliminar=\"" + escapeHtml(pedido.id) + "\"><i class=\"bi bi-trash\"></i></button></td></tr>";
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
        cargarPerfiles();
        cargarPedidosGuardados();
        var params = new URLSearchParams(window.location.search);
        if (params.get("perfil")) {
            agregarPerfil(params.get("perfil"), 1);
        }
        render();
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
            configsHoja[key] = configsHoja[key] || configDefaultHoja();
            configsHoja[key][input.getAttribute("data-hoja-campo")] = numero(input.value);
            render(true);
        });
        document.addEventListener("click", function (event) {
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
                var pedidoEliminar = event.target.closest("[data-pedido-eliminar]");
                if (pedidoEliminar) {
                    pedidosGuardados = pedidosGuardados.filter(function (pedido) { return pedido.id !== pedidoEliminar.getAttribute("data-pedido-eliminar"); });
                    persistirPedidosGuardados();
                    renderPedidosGuardados();
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
