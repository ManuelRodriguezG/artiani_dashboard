"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-29
     * Proposito: consultar y renderizar la primera pantalla operativa del ERP.
     * Impacto: Resumen ERP; solo consume endpoint read-only y abre rutas existentes.
     * Contrato: no ejecuta POST ni modifica entidades operativas.
     */
    document.addEventListener("DOMContentLoaded", function () {
        var refrescar = document.getElementById("resumen_refrescar");
        if (refrescar) {
            refrescar.addEventListener("click", cargarResumen);
        }
        cargarResumen();
    });

    function cargarResumen() {
        fetch("/inicio/resumen_erp", {credentials: "same-origin", headers: {"Accept": "application/json"}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("No fue posible consultar el resumen");
                }
                return response.json();
            })
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible consultar el resumen");
                }
                renderResumen(response.depurar || {});
            })
            .catch(function (error) {
                renderError(error.message);
            });
    }

    function renderResumen(data) {
        var notificaciones = data.notificaciones || {};
        var modulos = data.modulos || {};
        var ventas = modulos.ventas || {};
        var compras = modulos.compras || {};
        var inventario = modulos.inventario || {};

        texto("resumen_fecha", "Actualizado " + fechaCorta(data.fecha));
        texto("kpi_pendientes", numero(notificaciones.total));
        texto("kpi_criticas", numero(notificaciones.criticas));
        texto("kpi_ventas_hoy", numero(ventas.ventas_hoy));
        texto("kpi_total_hoy", dinero(ventas.total_hoy));
        texto("kpi_turnos", numero(ventas.turnos_abiertos));
        texto("kpi_por_recibir", Number(compras.ordenes_enviadas || 0) + Number(compras.ordenes_parciales || 0));
        texto("kpi_inv_pendientes", Number(inventario.pos_pendientes || 0) + Number(inventario.stock_bajo || 0));

        renderAlertas(notificaciones.items || []);
        renderAcciones(data.acciones || []);
        renderModulos(modulos);
        renderAviso("");
    }

    function renderAlertas(items) {
        var contenedor = document.getElementById("resumen_alertas");
        if (!contenedor) {
            return;
        }
        if (!items.length) {
            contenedor.innerHTML = '<div class="text-center text-muted py-10">Sin alertas operativas activas</div>';
            return;
        }
        contenedor.innerHTML = items.map(function (item) {
            var url = normalizarUrl(item.url_accion);
            var boton = url === "#" ? "" : '<a class="btn btn-sm btn-light-primary" href="' + escapeAttr(url) + '">Abrir</a>';
            return '<div class="resumen-alert-row py-3 d-flex flex-stack gap-3">' +
                '<div class="min-w-0">' +
                    '<div class="d-flex align-items-center gap-2 mb-1">' +
                        '<span class="' + clasePrioridad(item.prioridad) + '">' + escapeHtml(item.prioridad || "normal") + '</span>' +
                        '<span class="text-muted fs-8">' + escapeHtml(item.modulo_origen || "general") + '</span>' +
                    '</div>' +
                    '<div class="fw-bold text-gray-800">' + escapeHtml(item.titulo || "Notificacion") + '</div>' +
                    '<div class="text-muted fs-7">' + escapeHtml(item.descripcion || "") + '</div>' +
                '</div>' +
                '<div class="flex-shrink-0">' + boton + '</div>' +
            '</div>';
        }).join("");
    }

    function renderAcciones(acciones) {
        var contenedor = document.getElementById("resumen_acciones");
        if (!contenedor) {
            return;
        }
        if (!acciones.length) {
            contenedor.innerHTML = '<div class="col-12 text-muted">Sin accesos disponibles para tus permisos</div>';
            return;
        }
        contenedor.innerHTML = acciones.map(function (accion) {
            return '<div class="col-sm-6">' +
                '<a class="resumen-action d-flex align-items-center gap-3 p-3 text-gray-800 text-hover-primary" href="' + escapeAttr(accion.url || "#") + '">' +
                    '<i class="bi ' + escapeAttr(accion.icono || "bi-arrow-right") + ' fs-2 text-primary"></i>' +
                    '<span class="fw-semibold">' + escapeHtml(accion.titulo || "Abrir") + '</span>' +
                '</a>' +
            '</div>';
        }).join("");
    }

    function renderModulos(modulos) {
        var tarjetas = [
            tarjeta("Ventas/POS", "bi-receipt", "/ventas/mostrar", modulos.ventas, [
                ["Ventas hoy", "ventas_hoy"], ["Total hoy", "total_hoy", "money"], ["Pedidos", "pedidos_abiertos"], ["Venta rapida", "venta_rapida_pendientes"]
            ]),
            tarjeta("Compras", "bi-cart-check", "/compra/mostrar_compra_ordenes", modulos.compras, [
                ["Solicitudes", "solicitudes_pendientes"], ["OC abiertas", "ordenes_abiertas"], ["Por recibir", "ordenes_enviadas"], ["Saldo", "saldo_pendiente", "money"]
            ]),
            tarjeta("Almacen", "bi-building", "/almacen/mostrar_recepciones", modulos.almacen, [
                ["Recepciones", "recepciones_pendientes"], ["Parciales", "recepciones_parciales"], ["Etiquetas", "etiquetas_pendientes"], ["Incidencias", "incidencias_pendientes"]
            ]),
            tarjeta("Inventario", "bi-clipboard-data", "/inventario/productos_existencias", modulos.inventario, [
                ["SKUs", "skus_con_existencia"], ["Stock bajo", "stock_bajo"], ["Reservas", "reservas_pendientes"], ["POS pend.", "pos_pendientes"]
            ]),
            tarjeta("Catalogo", "bi-box-seam", "/catalogoerp/configuracion", modulos.catalogo, [
                ["Sin SKU", "productos_sin_sku"], ["Sin marca", "productos_sin_marca"], ["Sin precio", "skus_sin_precio"], ["Incidencias", "incidencias_abiertas"]
            ]),
            tarjeta("Proveedores", "bi-truck", "/proveedor/mostrar_proveedores_erp", modulos.proveedores, [
                ["Activos", "proveedores_activos"], ["Listas", "listas"], ["Costos", "costos_vigentes"], ["Incidencias", "incidencias_pendientes"]
            ]),
            tarjeta("CRM Clientes", "bi-person-vcard", "/crm/clientes", modulos.crm, [
                ["Clientes", "clientes_total"], ["Activos", "clientes_activos"], ["Calidad", "calidad_revisar"], ["Tareas", "tareas_pendientes"]
            ]),
            tarjeta("TMS Delivery", "bi-signpost-split", "/tms/servicios", modulos.tms, [
                ["Servicios", "total"], ["Abiertos", "abiertos"], ["En ruta", "en_ruta"], ["Cobro pend.", "cobro_pendiente"]
            ])
        ].filter(Boolean);
        document.getElementById("resumen_modulos").innerHTML = tarjetas.join("") || '<div class="col-12 text-muted">Sin modulos visibles para tus permisos</div>';
    }

    function tarjeta(titulo, icono, url, data, campos) {
        data = data || {};
        if (data.visible === false) {
            return "";
        }
        if (data.pendiente_schema) {
            return '<div class="col-md-6 col-xl-4"><div class="resumen-card p-5 h-100">' +
                encabezadoTarjeta(titulo, icono, url) +
                '<div class="alert alert-warning mb-0 py-3">' + escapeHtml(data.mensaje || "Esquema pendiente") + '</div>' +
            '</div></div>';
        }
        return '<div class="col-md-6 col-xl-4"><div class="resumen-card p-5 h-100">' +
            encabezadoTarjeta(titulo, icono, url) +
            '<div class="row g-3">' + campos.map(function (campo) {
                var valor = campo[2] === "money" ? dinero(data[campo[1]]) : numero(data[campo[1]]);
                return '<div class="col-6"><div class="text-muted fs-8 text-uppercase">' + escapeHtml(campo[0]) + '</div><div class="fw-bold fs-4">' + valor + '</div></div>';
            }).join("") + '</div>' +
        '</div></div>';
    }

    function encabezadoTarjeta(titulo, icono, url) {
        return '<div class="d-flex flex-stack mb-4">' +
            '<div class="d-flex align-items-center gap-3"><i class="bi ' + escapeAttr(icono) + ' fs-2 text-primary"></i><div class="fw-bold fs-5">' + escapeHtml(titulo) + '</div></div>' +
            '<a class="btn btn-sm btn-icon btn-light" href="' + escapeAttr(url) + '" title="Abrir"><i class="bi bi-arrow-right"></i></a>' +
        '</div>';
    }

    function renderError(mensaje) {
        renderAviso('<div class="alert alert-danger mb-0">' + escapeHtml(mensaje || "Error al cargar resumen") + '</div>');
    }

    function renderAviso(html) {
        var alerta = document.getElementById("resumen_alerta");
        if (alerta) {
            alerta.innerHTML = html || "";
        }
    }

    function texto(id, valor) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = valor == null ? "" : String(valor);
        }
    }

    function numero(value) {
        return Number(value || 0);
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function fechaCorta(value) {
        return value ? String(value) : "";
    }

    function clasePrioridad(prioridad) {
        prioridad = String(prioridad || "").toLowerCase();
        if (prioridad === "critica") {
            return "badge badge-light-danger";
        }
        if (prioridad === "alta") {
            return "badge badge-light-warning";
        }
        if (prioridad === "info") {
            return "badge badge-light-info";
        }
        return "badge badge-light-primary";
    }

    function normalizarUrl(url) {
        if (!url || typeof url !== "string" || url.indexOf("javascript:") === 0) {
            return "#";
        }
        return url;
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, "&quot;");
    }
})();
