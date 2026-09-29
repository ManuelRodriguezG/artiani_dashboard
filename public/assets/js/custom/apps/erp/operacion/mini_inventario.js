"use strict";
(function () {
    var catalogos = {almacenes: [], proveedores: [], categorias: []};
    var resultados = [];
    var docs = {};
    var activeId = "";
    var draftDoc = null;
    var ocultarOk = false;
    var storageKey = "erp_mini_inventarios_operativos_v2";

    function esc(v) { var d = document.createElement("div"); d.textContent = v == null ? "" : String(v); return d.innerHTML; }
    function attr(v) { return esc(v).replace(/"/g, "&quot;").replace(/'/g, "&#39;"); }
    function num(v) { var n = Number(String(v == null ? "" : v).replace(",", ".")); return Number.isFinite(n) ? n : 0; }
    function fmt(v) { return Number(num(v).toFixed(3)).toString(); }
    function nowIso() { return new Date().toISOString(); }

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (r) { return r.json(); });
    }

    function toast(tipo, mensaje) {
        if (typeof toastr !== "undefined") {
            toastr[tipo === "success" ? "success" : (tipo === "warning" ? "warning" : "info")](mensaje);
            return;
        }
        window.alert(mensaje);
    }

    function uid() {
        return "MINI-" + new Date().toISOString().replace(/[-:.TZ]/g, "").slice(0, 14) + "-" + Math.random().toString(36).slice(2, 6).toUpperCase();
    }

    function imagen(item) {
        var url = String(item.imagen_portada || "").trim();
        if (url && !/^(https?:)?\/\//i.test(url) && url.indexOf("/") !== 0 && url.indexOf("data:") !== 0) {
            url = "/" + url;
        }
        if (!url) {
            return "<div class=\"mini-inv-img\"><i class=\"bi bi-image\"></i></div>";
        }
        return "<div class=\"mini-inv-img\" style=\"background-image:url('" + attr(url) + "')\"></div>";
    }

    function cargarLocal() {
        try {
            var data = JSON.parse(localStorage.getItem(storageKey) || "{}") || {};
            docs = data.docs || {};
            activeId = data.activeId || "";
        } catch (e) {
            docs = {};
            activeId = "";
        }
        var hashId = decodeURIComponent(String(window.location.hash || "").replace(/^#/, ""));
        if (hashId === "nuevo") {
            crearBorradorNuevo();
            return;
        }
        if (hashId && docs[hashId]) {
            activeId = hashId;
        }
        if (!activeId || !docs[activeId]) {
            activeId = Object.keys(docs).sort(function (a, b) {
                return String(docs[b].updated_at || "").localeCompare(String(docs[a].updated_at || ""));
            })[0] || "";
        }
    }

    function guardarLocal() {
        localStorage.setItem(storageKey, JSON.stringify({activeId: activeId, docs: docs}));
    }

    function guardarDocActual() {
        if (activeId === "__draft__") { return; }
        guardarLocal();
    }

    function docActivo() {
        if (activeId === "__draft__") { return draftDoc; }
        return activeId && docs[activeId] ? docs[activeId] : null;
    }

    function docBase(id, nombre) {
        return {
            id: id,
            nombre: nombre || "",
            estado: "borrador",
            created_at: nowIso(),
            updated_at: nowIso(),
            filtros: {},
            items: {},
            guardado: false
        };
    }

    function crearBorradorNuevo() {
        draftDoc = docBase("__draft__", "");
        activeId = "__draft__";
    }

    function guardarBorrador() {
        var d = docActivo();
        if (!d) { return; }
        d.nombre = document.getElementById("mini_inv_nombre").value.trim();
        d.estado = document.getElementById("mini_inv_estado_doc").value || "borrador";
        if (!d.nombre) {
            toast("warning", "Agrega un nombre antes de guardar.");
            document.getElementById("mini_inv_nombre").focus();
            return;
        }
        if (activeId === "__draft__") {
            var id = uid();
            d.id = id;
            d.guardado = true;
            d.updated_at = nowIso();
            docs[id] = d;
            draftDoc = null;
            activeId = id;
            window.location.hash = encodeURIComponent(id);
        } else {
            d.guardado = true;
            d.updated_at = nowIso();
        }
        guardarLocal();
        renderDocs();
        sincronizarDocUi();
        renderInventario();
        toast("success", "Mini inventario guardado");
    }

    function crearDoc(nombre) {
        var id = uid();
        docs[id] = docBase(id, nombre || ("Mini inventario " + new Date().toLocaleDateString("es-MX")));
        docs[id].guardado = true;
        activeId = id;
        window.location.hash = encodeURIComponent(id);
        guardarLocal();
        sincronizarDocUi();
        renderDocs();
        renderInventario();
    }

    function llenarSelect(id, rows, valueKey, textKey, inicial) {
        var el = document.getElementById(id);
        el.innerHTML = "<option value=\"\">" + inicial + "</option>" + (rows || []).map(function (x) {
            return "<option value=\"" + esc(x[valueKey]) + "\">" + esc(x[textKey]) + "</option>";
        }).join("");
    }

    function cargarCatalogos() {
        return request("/operacion/mini_inventario_catalogos_erp").then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            catalogos = r.depurar || catalogos;
            llenarSelect("mini_inv_almacen", catalogos.almacenes, "id_almacen", "almacen", "Todas");
            llenarSelect("mini_inv_proveedor", catalogos.proveedores, "id_proveedor", "proveedor", "Todos");
            llenarSelect("mini_inv_categoria", catalogos.categorias, "id_categoria_erp", "categoria", "Todas");
        });
    }

    function renderDocs() {
        var el = document.getElementById("mini_inv_documento");
        var ids = Object.keys(docs).sort(function (a, b) {
            return String(docs[b].updated_at || "").localeCompare(String(docs[a].updated_at || ""));
        });
        var opciones = [];
        if (activeId === "__draft__" && draftDoc) {
            opciones.push("<option value=\"__draft__\" selected>Nuevo sin guardar</option>");
        }
        opciones = opciones.concat(ids.map(function (id) {
            var d = docs[id];
            return "<option value=\"" + esc(id) + "\"" + (id === activeId ? " selected" : "") + ">" + esc(d.nombre || id) + " - " + esc(d.estado || "borrador") + "</option>";
        }));
        el.innerHTML = opciones.length ? opciones.join("") : "<option value=\"\">Sin mini inventarios</option>";
    }

    function sincronizarDocUi() {
        var d = docActivo();
        document.getElementById("mini_inv_nombre").value = d ? d.nombre || "" : "";
        document.getElementById("mini_inv_estado_doc").value = d ? d.estado || "borrador" : "borrador";
        if (d && d.filtros) {
            document.getElementById("mini_inv_almacen").value = d.filtros.id_almacen || "";
            document.getElementById("mini_inv_proveedor").value = d.filtros.id_proveedor || "";
            document.getElementById("mini_inv_categoria").value = d.filtros.id_categoria_erp || "";
            document.getElementById("mini_inv_tipo").value = d.filtros.tipo || "";
            document.getElementById("mini_inv_buscar").value = d.filtros.q || "";
        }
    }

    function guardarMetaDoc() {
        var d = docActivo();
        if (!d) { return; }
        d.nombre = document.getElementById("mini_inv_nombre").value.trim();
        d.estado = document.getElementById("mini_inv_estado_doc").value || "borrador";
        d.updated_at = nowIso();
        guardarDocActual();
        renderDocs();
    }

    function filtrosActuales() {
        return {
            id_almacen: document.getElementById("mini_inv_almacen").value,
            id_proveedor: document.getElementById("mini_inv_proveedor").value,
            id_categoria_erp: document.getElementById("mini_inv_categoria").value,
            tipo: document.getElementById("mini_inv_tipo").value,
            q: document.getElementById("mini_inv_buscar").value.trim(),
            limite: "80"
        };
    }

    function buscarProductos() {
        var d = docActivo();
        if (!d) {
            crearBorradorNuevo();
            d = docActivo();
        }
        var filtros = filtrosActuales();
        if (!filtros.q && !filtros.id_proveedor && !filtros.id_categoria_erp && !filtros.tipo) {
            toast("warning", "Usa busqueda, proveedor, categoria o enfoque para no cargar todo el catalogo.");
            return Promise.resolve();
        }
        d.filtros = filtros;
        d.updated_at = nowIso();
        guardarDocActual();
        document.getElementById("mini_inv_resultados_card").style.display = "";
        document.getElementById("mini_inv_resultados_estado").textContent = "Buscando productos...";
        return request("/operacion/mini_inventario_productos_erp?" + new URLSearchParams(filtros).toString()).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            resultados = (r.depurar && r.depurar.items) || [];
            renderResultados();
        }).catch(function (e) {
            document.getElementById("mini_inv_resultados_estado").textContent = "No se pudo buscar.";
            toast("warning", e.message || String(e));
        });
    }

    function capturaDefault() {
        return {listo: "", pedido: "", minimo: "", maximo: "", responsable: "", accion: "auto", nota: ""};
    }

    function itemDocCompleto(item) {
        var c = item.captura || capturaDefault();
        item.listo_venta = c.listo === "" ? "" : num(c.listo);
        item.cantidad_trabajo = c.pedido === "" ? "" : num(c.pedido);
        item.minimo_operativo = c.minimo === "" || c.minimo == null ? num(item.stock_minimo || 0) : num(c.minimo);
        item.maximo_operativo = c.maximo === "" || c.maximo == null ? (item.stock_maximo === null || item.stock_maximo === "" ? null : num(item.stock_maximo)) : num(c.maximo);
        item.responsable = c.responsable || "";
        item.accion_manual = c.accion || "auto";
        item.nota = c.nota || "";
        return item;
    }

    function itemsDoc() {
        var d = docActivo();
        if (!d) { return []; }
        return Object.keys(d.items || {}).map(function (id) { return itemDocCompleto(d.items[id]); });
    }

    function agregarProducto(idSku) {
        var d = docActivo();
        if (!d) {
            crearBorradorNuevo();
            d = docActivo();
        }
        var item = resultados.find(function (x) { return String(x.id_sku) === String(idSku); });
        if (!item) { return; }
        if (!d.items) { d.items = {}; }
        if (!d.items[item.id_sku]) {
            d.items[item.id_sku] = Object.assign({}, item, {captura: capturaDefault(), agregado_at: nowIso()});
        }
        d.updated_at = nowIso();
        guardarDocActual();
        renderResultados();
        renderInventario();
        toast("success", "Producto agregado al mini inventario");
    }

    function quitarProducto(idSku) {
        var d = docActivo();
        if (!d || !d.items || !d.items[idSku]) { return; }
        delete d.items[idSku];
        d.updated_at = nowIso();
        guardarDocActual();
        renderInventario();
        renderResultados();
    }

    function calcularSugerido(item) {
        var listo = item.listo_venta === "" ? num(item.existencia_sistema || 0) : num(item.listo_venta);
        var minimo = num(item.minimo_operativo || 0);
        var maximo = item.maximo_operativo === null || item.maximo_operativo === "" ? 0 : num(item.maximo_operativo);
        var reorden = num(item.punto_reorden || 0);
        var umbral = reorden > 0 ? reorden : minimo;
        if (umbral <= 0 && maximo <= 0) { return 0; }
        if (umbral > 0 && listo > umbral) { return 0; }
        if (maximo > listo) { return Number((maximo - listo).toFixed(3)); }
        if (umbral > listo) { return Number((umbral - listo).toFixed(3)); }
        return 0;
    }

    function accionFinal(item, sugerido) {
        if (item.accion_manual && item.accion_manual !== "auto") { return item.accion_manual; }
        if (sugerido <= 0) { return "sin_accion"; }
        if (item.accion_sugerida === "reempacar" || item.accion_sugerida === "abrir_empaque" || item.accion_sugerida === "etiquetar") {
            return item.accion_sugerida;
        }
        return "comprar";
    }

    function cantidadFinal(item, sugerido) {
        return item.cantidad_trabajo === "" ? sugerido : num(item.cantidad_trabajo);
    }

    function badgeAccion(accion) {
        var mapa = {
            sin_accion: ["Sin accion", "badge-light-success"],
            reempacar: ["Reempacar", "badge-light-primary"],
            abrir_empaque: ["Abrir empaque", "badge-light-info"],
            etiquetar: ["Etiquetar", "badge-light-warning"],
            comprar: ["Comprar", "badge-light-danger"],
            revisar: ["Revisar", "badge-light-secondary"]
        };
        var x = mapa[accion] || mapa.revisar;
        return "<span class=\"badge " + x[1] + "\">" + x[0] + "</span>";
    }

    function opcionesAccion(actual) {
        var opciones = [
            ["auto", "Auto"],
            ["sin_accion", "Sin accion"],
            ["reempacar", "Reempacar"],
            ["abrir_empaque", "Abrir empaque"],
            ["etiquetar", "Etiquetar"],
            ["comprar", "Comprar"],
            ["revisar", "Revisar"]
        ];
        return opciones.map(function (op) {
            return "<option value=\"" + op[0] + "\"" + (actual === op[0] ? " selected" : "") + ">" + op[1] + "</option>";
        }).join("");
    }

    function renderResultados() {
        var d = docActivo();
        var actuales = d && d.items ? d.items : {};
        document.getElementById("mini_inv_resultados_estado").textContent = resultados.length + " resultado(s). Agrega solo los productos que pertenecen a este mini inventario.";
        document.getElementById("mini_inv_resultados_body").innerHTML = resultados.map(function (item) {
            var agregado = !!actuales[item.id_sku];
            var origen = item.sku_origen ? "<div class=\"text-muted fs-8\">Origen: " + esc(item.sku_origen) + "</div>" : "";
            return "<tr>" +
                "<td><div class=\"d-flex align-items-center gap-3\">" + imagen(item) + "<div><div class=\"fw-bold\">" + esc(item.sku) + "</div><div class=\"text-muted fs-8\">" + esc(item.nombre_sku || item.producto || "") + "</div><div class=\"text-muted fs-8\">" + esc(item.categoria || "") + "</div></div></div></td>" +
                "<td><div class=\"fw-bold\">" + esc(item.proveedor || "Sin proveedor preferido") + "</div><div class=\"text-muted fs-8\">" + esc(item.sku_proveedor || "") + "</div>" + origen + "</td>" +
                "<td class=\"text-end\">" + fmt(item.stock_minimo) + "</td>" +
                "<td class=\"text-end\">" + (item.stock_maximo === null ? "-" : fmt(item.stock_maximo)) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm " + (agregado ? "btn-light-success" : "btn-light-primary") + "\" data-mini-add=\"" + esc(item.id_sku) + "\" type=\"button\">" + (agregado ? "Agregado" : "Agregar") + "</button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-8\">Sin resultados</td></tr>";
    }

    function card(titulo, valor, icono, clase) {
        return "<div class=\"border rounded px-4 py-3 " + clase + "\"><div class=\"d-flex align-items-center gap-3\"><i class=\"bi " + icono + " fs-2\"></i><div><div class=\"fw-bold fs-5\">" + esc(valor) + "</div><div class=\"text-muted fs-8\">" + esc(titulo) + "</div></div></div></div>";
    }

    function renderResumen(rows) {
        var total = rows.length;
        var tareas = rows.filter(function (item) { return accionFinal(item, calcularSugerido(item)) !== "sin_accion"; }).length;
        var reempacar = rows.filter(function (item) { return accionFinal(item, calcularSugerido(item)) === "reempacar"; }).length;
        var abrir = rows.filter(function (item) { return accionFinal(item, calcularSugerido(item)) === "abrir_empaque"; }).length;
        var comprar = rows.filter(function (item) { return accionFinal(item, calcularSugerido(item)) === "comprar"; }).length;
        document.getElementById("mini_inv_resumen").innerHTML =
            card("Productos", total, "bi-box-seam", "bg-light-primary") +
            card("Con tarea", tareas, "bi-list-task", "bg-light-warning") +
            card("Reempacar", reempacar, "bi-bag-plus", "bg-light-info") +
            card("Abrir", abrir, "bi-box-arrow-up", "bg-light-info") +
            card("Comprar", comprar, "bi-cart-plus", "bg-light-danger");
    }

    function renderPlanApertura(rows) {
        var grupos = {};
        rows.forEach(function (item) {
            var sugerido = calcularSugerido(item);
            var accion = accionFinal(item, sugerido);
            var cantidad = cantidadFinal(item, sugerido);
            var factor = num(item.factor_operativo || 0);
            if (accion !== "abrir_empaque" || cantidad <= 0 || !item.sku_origen || factor <= 0) { return; }
            var key = item.sku_origen;
            if (!grupos[key]) {
                grupos[key] = {sku_origen: item.sku_origen, factor: factor, requeridas: 0, destinos: []};
            }
            grupos[key].requeridas += cantidad;
            grupos[key].destinos.push(item.sku + " x " + fmt(cantidad));
        });

        var keys = Object.keys(grupos).sort();
        var cardEl = document.getElementById("mini_inv_plan_apertura_card");
        var bodyEl = document.getElementById("mini_inv_plan_apertura");
        if (!keys.length) {
            cardEl.style.display = "none";
            bodyEl.innerHTML = "";
            return;
        }
        cardEl.style.display = "";
        bodyEl.innerHTML = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>SKU origen</th><th class=\"text-end\">Factor</th><th class=\"text-end\">Necesario</th><th class=\"text-end\">Abrir empaques</th><th class=\"text-end\">Salida generada</th><th class=\"text-end\">Sobrante operativo</th><th>Destino</th></tr></thead><tbody>" +
            keys.map(function (key) {
                var g = grupos[key];
                var empaques = Math.ceil(g.requeridas / g.factor);
                var salida = empaques * g.factor;
                var sobrante = salida - g.requeridas;
                return "<tr>" +
                    "<td class=\"fw-bold\">" + esc(g.sku_origen) + "</td>" +
                    "<td class=\"text-end\">" + fmt(g.factor) + "</td>" +
                    "<td class=\"text-end\">" + fmt(g.requeridas) + "</td>" +
                    "<td class=\"text-end fw-bold\">" + esc(empaques) + "</td>" +
                    "<td class=\"text-end\">" + fmt(salida) + "</td>" +
                    "<td class=\"text-end\">" + fmt(sobrante) + "</td>" +
                    "<td class=\"text-muted fs-8\">" + esc(g.destinos.join(", ")) + "</td>" +
                    "</tr>";
            }).join("") +
            "</tbody></table></div><div class=\"text-muted fs-7 mt-3\">El sobrante operativo no se trata como merma: queda como producto abierto disponible para venta suelta o revision operativa.</div>";
    }

    function renderInventario() {
        var d = docActivo();
        var rowsAll = itemsDoc();
        var rows = rowsAll.filter(function (item) {
            return !(ocultarOk && accionFinal(item, calcularSugerido(item)) === "sin_accion");
        });
        renderResumen(rowsAll);
        renderPlanApertura(rowsAll);
        document.getElementById("mini_inv_estado").textContent = d ? (rowsAll.length + " producto(s) en " + (d.nombre || d.id) + ". Ultima edicion local: " + new Date(d.updated_at || d.created_at).toLocaleString("es-MX")) : "Crea un mini inventario para empezar.";
        document.getElementById("mini_inv_body").innerHTML = rows.map(function (item) {
            var sugerido = calcularSugerido(item);
            var accion = accionFinal(item, sugerido);
            var cantidad = cantidadFinal(item, sugerido);
            var origen = item.sku_origen ? "<div class=\"text-muted fs-8\">Origen: " + esc(item.sku_origen) + (item.factor_operativo ? " | factor " + esc(item.factor_operativo) : "") + "</div>" : "";
            return "<tr data-sku=\"" + esc(item.id_sku) + "\">" +
                "<td><div class=\"d-flex align-items-center gap-3\">" + imagen(item) + "<div><div class=\"fw-bold\">" + esc(item.sku) + "</div><div class=\"text-muted fs-8\">" + esc(item.nombre_sku || item.producto || "") + "</div><div class=\"text-muted fs-8\">" + esc(item.categoria || "") + "</div></div></div></td>" +
                "<td><div class=\"fw-bold\">" + esc(item.proveedor || "Sin proveedor preferido") + "</div><div class=\"text-muted fs-8\">" + esc(item.sku_proveedor || "") + "</div>" + origen + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid mini-inv-num\" inputmode=\"decimal\" data-mini-campo=\"minimo\" value=\"" + esc(fmt(item.minimo_operativo)) + "\" placeholder=\"0\"></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid mini-inv-num\" inputmode=\"decimal\" data-mini-campo=\"maximo\" value=\"" + esc(item.maximo_operativo === null ? "" : fmt(item.maximo_operativo)) + "\" placeholder=\"Sin max\"></td>" +
                "<td class=\"text-end\">" + fmt(item.existencia_sistema) + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid mini-inv-num\" inputmode=\"decimal\" data-mini-campo=\"listo\" value=\"" + esc(item.listo_venta === "" ? "" : fmt(item.listo_venta)) + "\" placeholder=\"0\"></td>" +
                "<td class=\"text-end fw-bold\">" + fmt(sugerido) + " " + esc(item.unidad_venta || "") + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid mini-inv-num\" inputmode=\"decimal\" data-mini-campo=\"pedido\" value=\"" + esc(item.cantidad_trabajo === "" ? "" : fmt(cantidad)) + "\" placeholder=\"" + esc(fmt(sugerido)) + "\"></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid mini-inv-select\" data-mini-campo=\"accion\">" + opcionesAccion(item.accion_manual || "auto") + "</select><div class=\"mt-1\">" + badgeAccion(accion) + "</div></td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid\" data-mini-campo=\"responsable\" value=\"" + esc(item.responsable || "") + "\" placeholder=\"Responsable\"></td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid mini-inv-note\" data-mini-campo=\"nota\" value=\"" + esc(item.nota || "") + "\" placeholder=\"Nota\"></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-danger\" data-mini-remove=\"" + esc(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-x-lg\"></i></button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"12\" class=\"text-center text-muted py-10\">Este mini inventario aun no tiene productos. Busca y agrega solo los productos que quieres revisar.</td></tr>";
    }

    function actualizarCaptura(row, campo, valor) {
        var d = docActivo();
        var id = row.getAttribute("data-sku");
        if (!d || !d.items || !d.items[id]) { return; }
        var actual = d.items[id].captura || capturaDefault();
        actual[campo] = valor;
        d.items[id].captura = actual;
        d.updated_at = nowIso();
        guardarDocActual();
        if (campo === "accion" || campo === "listo" || campo === "pedido" || campo === "minimo" || campo === "maximo") {
            renderInventario();
        }
    }

    function filasTareas() {
        return itemsDoc().map(function (item) {
            var sugerido = calcularSugerido(item);
            var accion = accionFinal(item, sugerido);
            return {item: item, sugerido: sugerido, cantidad: cantidadFinal(item, sugerido), accion: accion};
        }).filter(function (x) { return x.accion !== "sin_accion"; });
    }

    function filasCompra() {
        return filasTareas().filter(function (x) { return x.accion === "comprar" && num(x.cantidad) > 0; });
    }

    function csv(v) {
        var s = String(v == null ? "" : v);
        return "\"" + s.replace(/"/g, "\"\"") + "\"";
    }

    function exportarCsv() {
        var d = docActivo();
        var encabezado = ["folio_local", "nombre", "estado", "sku", "producto", "proveedor", "sku_origen", "minimo", "maximo", "sistema", "listo_venta", "sugerido", "cantidad", "accion", "responsable", "nota"];
        var lineas = [encabezado.join(",")].concat(itemsDoc().map(function (item) {
            var sugerido = calcularSugerido(item);
            var accion = accionFinal(item, sugerido);
            var cantidad = cantidadFinal(item, sugerido);
            return [d ? d.id : "", d ? d.nombre : "", d ? d.estado : "", item.sku, item.nombre_sku || item.producto, item.proveedor, item.sku_origen, item.minimo_operativo, item.maximo_operativo, item.existencia_sistema, item.listo_venta, sugerido, cantidad, accion, item.responsable, item.nota].map(csv).join(",");
        }));
        var blob = new Blob([lineas.join("\r\n")], {type: "text/csv;charset=utf-8"});
        var a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = (d ? d.id : "mini_inventario") + ".csv";
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    function copiarTexto(texto, ok) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(texto).then(function () { toast("success", ok); });
            return;
        }
        window.prompt("Copia el texto:", texto);
    }

    function copiarTareas() {
        var d = docActivo();
        var tareas = filasTareas();
        var texto = tareas.map(function (x) {
            return "- " + x.accion + " | " + x.item.sku + " | cantidad " + fmt(x.cantidad) + " " + (x.item.unidad_venta || "") + " | sugerido " + fmt(x.sugerido) + " | resp: " + (x.item.responsable || "por asignar") + (x.item.nota ? " | " + x.item.nota : "");
        }).join("\n");
        texto = tareas.length ? ((d ? (d.nombre + " | " + d.estado + "\n") : "") + texto) : "Sin tareas operativas pendientes.";
        copiarTexto(texto, "Tareas copiadas");
    }

    function copiarCompra() {
        var grupos = {};
        filasCompra().forEach(function (x) {
            var proveedor = x.item.proveedor || "Sin proveedor preferido";
            if (!grupos[proveedor]) { grupos[proveedor] = []; }
            grupos[proveedor].push(x);
        });
        var proveedores = Object.keys(grupos).sort();
        var d = docActivo();
        var texto = proveedores.map(function (proveedor) {
            var lineas = grupos[proveedor].map(function (x) {
                return "- " + x.item.sku + " | " + (x.item.nombre_sku || x.item.producto || "") + " | pedir " + fmt(x.cantidad) + " " + (x.item.unidad_venta || "") + (x.item.sku_proveedor ? " | sku prov: " + x.item.sku_proveedor : "") + (x.item.nota ? " | " + x.item.nota : "");
            });
            return "Proveedor: " + proveedor + "\n" + lineas.join("\n");
        }).join("\n\n");
        texto = proveedores.length ? ((d ? (d.nombre + " | " + d.estado + "\n\n") : "") + texto) : "Sin productos marcados para compra.";
        copiarTexto(texto, "Borrador de compra copiado");
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarLocal();
        cargarCatalogos().then(function () {
            renderDocs();
            sincronizarDocUi();
            renderInventario();
        }).catch(function (e) { toast("warning", e.message || String(e)); });

        document.getElementById("mini_inv_nuevo").addEventListener("click", function () {
            window.location.href = "/operacion/mini_inventario#nuevo";
            crearBorradorNuevo();
            renderDocs();
            sincronizarDocUi();
            renderInventario();
            renderResultados();
        });
        document.getElementById("mini_inv_guardar_doc").addEventListener("click", guardarBorrador);
        document.getElementById("mini_inv_descartar_doc").addEventListener("click", function () {
            if (activeId === "__draft__") {
                window.location.href = "/operacion/mini_inventarios";
                return;
            }
            window.location.href = "/operacion/mini_inventarios";
        });
        document.getElementById("mini_inv_documento").addEventListener("change", function () {
            activeId = this.value;
            if (activeId) {
                window.location.hash = encodeURIComponent(activeId);
            }
            guardarDocActual();
            sincronizarDocUi();
            renderInventario();
            renderResultados();
        });
        document.getElementById("mini_inv_nombre").addEventListener("input", guardarMetaDoc);
        document.getElementById("mini_inv_estado_doc").addEventListener("change", guardarMetaDoc);
        document.getElementById("mini_inv_consultar").addEventListener("click", buscarProductos);
        document.getElementById("mini_inv_exportar").addEventListener("click", exportarCsv);
        document.getElementById("mini_inv_copiar_compra").addEventListener("click", copiarCompra);
        document.getElementById("mini_inv_copiar_tareas").addEventListener("click", copiarTareas);
        document.getElementById("mini_inv_imprimir").addEventListener("click", function () { window.print(); });
        document.getElementById("mini_inv_ocultar_ok").addEventListener("click", function () {
            ocultarOk = !ocultarOk;
            this.classList.toggle("btn-light-success", ocultarOk);
            renderInventario();
        });
        document.getElementById("mini_inv_restaurar").addEventListener("click", function () {
            cargarLocal();
            renderDocs();
            sincronizarDocUi();
            renderInventario();
            renderResultados();
            toast("info", "Mini inventarios restaurados");
        });
        document.getElementById("mini_inv_limpiar").addEventListener("click", function () {
            var d = docActivo();
            if (!d || !window.confirm("Limpiar productos y captura de este mini inventario?")) { return; }
            d.items = {};
            d.updated_at = nowIso();
            guardarDocActual();
            renderInventario();
            renderResultados();
        });
        document.getElementById("mini_inv_eliminar_doc").addEventListener("click", function () {
            var d = docActivo();
            if (!d || !window.confirm("Eliminar este mini inventario local?")) { return; }
            if (activeId === "__draft__") {
                window.location.href = "/operacion/mini_inventarios";
                return;
            }
            delete docs[d.id];
            activeId = "";
            guardarLocal();
            cargarLocal();
            renderDocs();
            sincronizarDocUi();
            renderInventario();
            renderResultados();
        });
        document.getElementById("mini_inv_resultados_body").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-mini-add]");
            if (btn) { agregarProducto(btn.getAttribute("data-mini-add")); }
        });
        document.getElementById("mini_inv_body").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-mini-remove]");
            if (btn && window.confirm("Quitar producto de este mini inventario?")) {
                quitarProducto(btn.getAttribute("data-mini-remove"));
            }
        });
        document.getElementById("mini_inv_body").addEventListener("change", function (e) {
            var campo = e.target.getAttribute("data-mini-campo");
            var row = e.target.closest("tr[data-sku]");
            if (campo && row) { actualizarCaptura(row, campo, e.target.value); }
        });
        document.getElementById("mini_inv_body").addEventListener("input", function (e) {
            var campo = e.target.getAttribute("data-mini-campo");
            var row = e.target.closest("tr[data-sku]");
            if (!campo || !row) { return; }
            var d = docActivo();
            var id = row.getAttribute("data-sku");
            if (!d || !d.items || !d.items[id]) { return; }
            var actual = d.items[id].captura || capturaDefault();
            actual[campo] = e.target.value;
            d.items[id].captura = actual;
            d.updated_at = nowIso();
            guardarDocActual();
        });
    });
})();
