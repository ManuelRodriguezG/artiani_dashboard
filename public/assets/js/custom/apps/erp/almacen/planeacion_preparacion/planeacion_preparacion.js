"use strict";
(function () {
    var STORAGE_KEY = "erp_almacen_planeaciones_preparacion_v1";
    var recetas = [];
    var productos = [];
    var lineas = {};

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function numero(value, decimales) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: decimales, maximumFractionDigits: decimales});
    }

    function decimal(value) {
        var limpio = String(value || "0").trim();
        if (limpio.indexOf(",") >= 0 && limpio.indexOf(".") >= 0) {
            limpio = limpio.replace(/,/g, "");
        } else if (limpio.indexOf(",") >= 0) {
            limpio = limpio.replace(",", ".");
        }
        limpio = limpio.replace(/[^0-9.\-]/g, "");
        var n = Number(limpio);
        return isFinite(n) ? n : 0;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-10-01
     * Proposito: permite buscar productos con recetas de preparacion sin depender de almacen fisico.
     * Impacto: UI Planeaciones de preparacion; cambia el foco de existencia a reglas de Catalogo.
     * Contrato: el select trabaja sobre SKUs origen preparables devueltos por endpoint read-only.
     */
    function refrescarSelectProducto() {
        var jq = window.jQuery;
        var element = $("alm_plan_producto");
        if (!jq || !jq.fn || !jq.fn.select2 || !element) { return; }
        var select = jq(element);
        if (select.hasClass("select2-hidden-accessible")) {
            select.select2("destroy");
        }
        select.select2({
            placeholder: "Selecciona producto",
            allowClear: true,
            width: "100%"
        });
        select.off("change.almPlanProducto").on("change.almPlanProducto", alCambiarProducto);
    }

    function cargarRecetas() {
        return request("/almacen/preparacion_presentaciones_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            recetas = response.depurar || [];
            productos = productosDesdeRecetas();
            renderProductos();
            renderGuardadas();
            renderTodo();
        });
    }

    function productosDesdeRecetas() {
        var vistos = {};
        return recetas.reduce(function (acumulado, item) {
            var id = String(item.id_sku_base || "");
            if (id && !vistos[id]) {
                vistos[id] = true;
                acumulado.push(item);
            }
            return acumulado;
        }, []).sort(function (a, b) {
            return String(a.nombre_base || a.sku_base).localeCompare(String(b.nombre_base || b.sku_base));
        });
    }

    function renderProductos() {
        $("alm_plan_producto").innerHTML = "<option value=\"\">Selecciona producto</option>" + productos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_sku_base) + "\">" + escapeHtml(item.sku_base) + " - " + escapeHtml(item.nombre_base || item.producto || "") + "</option>";
        }).join("");
        if (!productos.length) {
            $("alm_plan_producto").innerHTML = "<option value=\"\">Sin productos con recetas</option>";
        }
        refrescarSelectProducto();
    }

    function productoActual() {
        var id = $("alm_plan_producto").value || "";
        return productos.find(function (item) { return String(item.id_sku_base) === String(id); }) || null;
    }

    function recetasProducto() {
        var id = $("alm_plan_producto").value || "";
        return recetas.filter(function (item) { return String(item.id_sku_base) === String(id); })
            .sort(function (a, b) { return Number(a.factor_salida_base || 0) - Number(b.factor_salida_base || 0); });
    }

    function claveReceta(item) {
        return (item.id_sku_transformacion ? "t" + item.id_sku_transformacion : "r" + item.id_sku_presentacion_regla);
    }

    function cantidadBasePlan() {
        var cantidad = decimal($("alm_plan_cantidad_base").value);
        return cantidad > 0 ? cantidad : 1;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-10-01
     * Proposito: genera escenarios de mezcla sobre las recetas reales del producto seleccionado.
     * Impacto: no escribe BD; ayuda a comparar combinaciones antes de ejecutar Preparacion/Empaque.
     * Contrato: usa cantidad base capturada y reparte por pesos configurados en UI.
     */
    function escenariosDisponibles() {
        var lista = recetasProducto();
        if (!lista.length) { return []; }
        return [
            {id: "balanceado", titulo: "Balanceado", detalle: "Distribuye la cantidad entre todas las recetas.", pesos: lista.map(function () { return 1; })},
            {id: "chica", titulo: "Mas chica", detalle: "Prioriza la presentacion de menor consumo unitario.", pesos: lista.map(function (_, i) { return i === 0 ? 3 : 1; })},
            {id: "grande", titulo: "Mas grande", detalle: "Prioriza la presentacion de mayor consumo unitario.", pesos: lista.map(function (_, i) { return i === lista.length - 1 ? 3 : 1; })}
        ].map(function (escenario) {
            escenario.recetas = lista;
            return escenario;
        });
    }

    function cargarEscenario(id) {
        var escenario = escenariosDisponibles().find(function (item) { return item.id === id; });
        if (!escenario) { return; }
        var cantidad = cantidadBasePlan();
        var totalPeso = escenario.pesos.reduce(function (sum, peso) { return sum + Number(peso || 0); }, 0) || 1;
        lineas = {};
        escenario.recetas.forEach(function (receta, index) {
            var factor = Number(receta.factor_salida_base || 0) * (1 + (Number(receta.merma_porcentaje || 0) / 100));
            var baseObjetivo = cantidad * (Number(escenario.pesos[index] || 0) / totalPeso);
            lineas[claveReceta(receta)] = factor > 0 ? Math.max(0, Math.floor(baseObjetivo / factor)) : 0;
        });
        renderTodo();
    }

    function consumoLinea(item) {
        var factor = Number(item.factor_salida_base || 0) * (1 + (Number(item.merma_porcentaje || 0) / 100));
        return Number(lineas[claveReceta(item)] || 0) * factor;
    }

    function renderRecetas() {
        var lista = recetasProducto();
        $("alm_plan_recetas").innerHTML = lista.map(function (item) {
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.sku_presentacion) + "</div>" +
                "<div class=\"text-muted fs-8 mb-3\">" + escapeHtml(item.nombre_presentacion || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2\">" +
                "<span class=\"badge badge-light-primary\">" + numero(item.factor_salida_base, 6) + " " + escapeHtml(item.unidad_base || "") + "</span>" +
                "<span class=\"badge badge-light-info\">" + escapeHtml(item.tipo_transformacion || "presentacion") + "</span>" +
                (Number(item.merma_porcentaje || 0) > 0 ? "<span class=\"badge badge-light-warning\">Merma " + numero(item.merma_porcentaje, 2) + "%</span>" : "") +
                "</div></div></div>";
        }).join("") || "<div class=\"col-12 text-muted\">Selecciona un producto para ver sus recetas.</div>";
    }

    function renderEscenarios() {
        var escenarios = escenariosDisponibles();
        $("alm_plan_escenarios").innerHTML = escenarios.map(function (item) {
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"fw-bold fs-6 mb-1\">" + escapeHtml(item.titulo) + "</div>" +
                "<div class=\"text-muted fs-8 mb-4\">" + escapeHtml(item.detalle) + "</div>" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-escenario=\"" + escapeHtml(item.id) + "\">Cargar escenario</button>" +
                "</div></div>";
        }).join("") || "<div class=\"col-12 text-muted\">Selecciona un producto con recetas.</div>";
    }

    function renderTabla() {
        var lista = recetasProducto();
        var consumoTotal = lista.reduce(function (sum, item) { return sum + consumoLinea(item); }, 0);
        $("alm_plan_body").innerHTML = lista.map(function (item) {
            var clave = claveReceta(item);
            var unidades = Number(lineas[clave] || 0);
            var factor = Number(item.factor_salida_base || 0) * (1 + (Number(item.merma_porcentaje || 0) / 100));
            var consumo = unidades * factor;
            var participacion = consumoTotal > 0 ? (consumo / consumoTotal) * 100 : 0;
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_presentacion) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_presentacion || "") + "</div></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end ms-auto alm-plan-unidades\" style=\"width: 8rem;\" inputmode=\"numeric\" data-linea=\"" + escapeHtml(clave) + "\" value=\"" + escapeHtml(unidades) + "\"></td>" +
                "<td class=\"text-end\">" + numero(factor, 6) + " " + escapeHtml(item.unidad_base || "") + "</td>" +
                "<td class=\"text-end fw-bold\">" + numero(consumo, 6) + "</td>" +
                "<td class=\"text-end\">" + numero(participacion, 1) + "%</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-10\">Selecciona un producto.</td></tr>";
    }

    function renderHoja() {
        var producto = productoActual();
        var lista = recetasProducto().filter(function (item) { return Number(lineas[claveReceta(item)] || 0) > 0; });
        var consumoTotal = lista.reduce(function (sum, item) { return sum + consumoLinea(item); }, 0);
        $("alm_plan_fecha").textContent = new Date().toLocaleString("es-MX");
        if (!producto) {
            $("alm_plan_hoja_contenido").innerHTML = "Selecciona un producto para generar la hoja.";
            return;
        }
        var filas = lista.map(function (item) {
            var unidades = Number(lineas[claveReceta(item)] || 0);
            var factor = Number(item.factor_salida_base || 0) * (1 + (Number(item.merma_porcentaje || 0) / 100));
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku_presentacion) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_presentacion || "") + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + escapeHtml(unidades) + "</td>" +
                "<td class=\"text-end\">" + numero(factor, 6) + "</td>" +
                "<td class=\"text-end\">" + numero(unidades * factor, 6) + " " + escapeHtml(item.unidad_base || "") + "</td></tr>";
        }).join("");
        $("alm_plan_hoja_contenido").innerHTML =
            "<div class=\"row g-4 mb-5\">" +
            "<div class=\"col-md-4\"><div class=\"text-muted fs-8\">Producto origen</div><div class=\"fw-bold\">" + escapeHtml(producto.sku_base) + "</div><div>" + escapeHtml(producto.nombre_base || producto.producto || "") + "</div></div>" +
            "<div class=\"col-md-4\"><div class=\"text-muted fs-8\">Planeacion</div><div class=\"fw-bold\">" + escapeHtml($("alm_plan_nombre").value || "Sin nombre") + "</div><div>" + escapeHtml($("alm_plan_responsable").value || "Responsable pendiente") + "</div></div>" +
            "<div class=\"col-md-4\"><div class=\"text-muted fs-8\">Consumo planeado</div><div class=\"fw-bold\">" + numero(consumoTotal, 6) + " / " + numero(cantidadBasePlan(), 6) + " " + escapeHtml(producto.unidad_base || "") + "</div></div>" +
            "</div>" +
            "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Preparar</th><th class=\"text-end\">Unidades</th><th class=\"text-end\">Consumo unitario</th><th class=\"text-end\">Consumo total</th></tr></thead><tbody>" +
            (filas || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Captura unidades para preparar.</td></tr>") +
            "</tbody></table></div>" +
            "<div class=\"alert alert-light mt-5 mb-0\">Esta hoja es una planeacion. La afectacion real se hace despues en Preparacion/Empaque.</div>";
    }

    function planesGuardados() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
        } catch (e) {
            return [];
        }
    }

    function guardarPlanes(planes) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(planes || []));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-10-01
     * Proposito: guarda planeaciones locales sin escribir BD productiva.
     * Impacto: permite probar CRUD visual de escenarios antes de solicitar DDL persistente.
     * Contrato: los datos quedan en localStorage del navegador; no son compartidos entre usuarios/equipos.
     */
    function guardarPlaneacion() {
        var producto = productoActual();
        if (!producto) {
            Swal.fire({text: "Selecciona un producto.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var id = $("alm_plan_id").value || ("PLAN-" + Date.now());
        var planes = planesGuardados().filter(function (item) { return item.id !== id; });
        planes.unshift({
            id: id,
            id_sku_base: String(producto.id_sku_base),
            sku_base: producto.sku_base,
            nombre_base: producto.nombre_base || producto.producto || "",
            unidad_base: producto.unidad_base || "",
            nombre: $("alm_plan_nombre").value || "Planeacion sin nombre",
            cantidad_base: cantidadBasePlan(),
            responsable: $("alm_plan_responsable").value || "",
            lineas: lineas,
            fecha_actualizacion: new Date().toISOString()
        });
        guardarPlanes(planes);
        $("alm_plan_id").value = id;
        renderGuardadas();
        Swal.fire({text: "Planeacion guardada en este navegador.", icon: "success", confirmButtonText: "Aceptar"});
    }

    function cargarPlaneacion(id) {
        var plan = planesGuardados().find(function (item) { return item.id === id; });
        if (!plan) { return; }
        $("alm_plan_id").value = plan.id;
        $("alm_plan_producto").value = String(plan.id_sku_base);
        $("alm_plan_nombre").value = plan.nombre || "";
        $("alm_plan_cantidad_base").value = numero(plan.cantidad_base || 1, 6);
        $("alm_plan_responsable").value = plan.responsable || "";
        lineas = plan.lineas || {};
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery("#alm_plan_producto").trigger("change.select2");
        }
        renderTodo();
    }

    function eliminarPlaneacion(id) {
        guardarPlanes(planesGuardados().filter(function (item) { return item.id !== id; }));
        if ($("alm_plan_id").value === id) {
            nuevaPlaneacion();
        }
        renderGuardadas();
    }

    function renderGuardadas() {
        var actuales = planesGuardados();
        $("alm_plan_guardadas").innerHTML = actuales.map(function (item) {
            return "<div class=\"border-bottom py-3\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku_base) + " - " + escapeHtml(item.nombre_base || "") + "</div>" +
                "<div class=\"d-flex gap-2 mt-3\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-plan-cargar=\"" + escapeHtml(item.id) + "\">Editar</button>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-plan-eliminar=\"" + escapeHtml(item.id) + "\">Eliminar</button></div>" +
                "</div>";
        }).join("") || "<div class=\"text-muted\">Sin planeaciones guardadas en este navegador.</div>";
    }

    function nuevaPlaneacion() {
        $("alm_plan_id").value = "";
        $("alm_plan_nombre").value = "";
        $("alm_plan_responsable").value = "";
        $("alm_plan_cantidad_base").value = "1";
        lineas = {};
        renderTodo();
    }

    function renderTodo() {
        renderRecetas();
        renderEscenarios();
        renderTabla();
        renderHoja();
    }

    function alCambiarProducto() {
        lineas = {};
        $("alm_plan_id").value = "";
        renderTodo();
    }

    document.addEventListener("click", function (event) {
        var escenario = event.target.closest("[data-escenario]");
        if (escenario) { cargarEscenario(escenario.getAttribute("data-escenario")); }
        var cargar = event.target.closest("[data-plan-cargar]");
        if (cargar) { cargarPlaneacion(cargar.getAttribute("data-plan-cargar")); }
        var eliminar = event.target.closest("[data-plan-eliminar]");
        if (eliminar) { eliminarPlaneacion(eliminar.getAttribute("data-plan-eliminar")); }
    });

    document.addEventListener("input", function (event) {
        if (event.target.classList.contains("alm-plan-unidades")) {
            lineas[event.target.getAttribute("data-linea")] = Math.max(0, parseInt(event.target.value || "0", 10) || 0);
            renderHoja();
        }
    });

    $("alm_plan_producto").addEventListener("change", alCambiarProducto);
    $("alm_plan_cantidad_base").addEventListener("input", renderTodo);
    $("alm_plan_nombre").addEventListener("input", renderHoja);
    $("alm_plan_responsable").addEventListener("input", renderHoja);
    $("alm_plan_limpiar").addEventListener("click", function () { lineas = {}; renderTodo(); });
    $("alm_plan_nueva").addEventListener("click", nuevaPlaneacion);
    $("alm_plan_guardar").addEventListener("click", guardarPlaneacion);
    $("alm_plan_imprimir").addEventListener("click", function () { window.print(); });

    cargarRecetas().catch(function (error) {
        Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
    });
})();
